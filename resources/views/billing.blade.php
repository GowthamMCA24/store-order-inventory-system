<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Store POS &mdash; Retail Billing Terminal</title>

    <!-- Tailwind CSS (Self-contained CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        [x-cloak] { display: none !important; }

        /* Custom POS Scrollbar */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: rgba(15, 23, 42, 0.6); }
        ::-webkit-scrollbar-thumb { background: rgba(51, 65, 85, 0.8); border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(71, 85, 105, 1); }

        @media print {
            body * { visibility: hidden; }
            #printable-receipt, #printable-receipt * { visibility: visible; }
            #printable-receipt {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                margin: 0;
                padding: 16px;
                box-shadow: none;
                border: none;
                color: #000;
            }
        }
    </style>
</head>
<body class="h-full text-slate-100 flex flex-col overflow-hidden bg-slate-950 select-none" 
      x-data="posTerminal()" 
      x-init="init()" 
      @keydown.window.ctrl.enter.prevent="generateBill()" 
      @keydown.window.escape="showReceiptModal = false; showHistoryModal = false; showLowStockDrawer = false"
      x-cloak>

    <!-- ==============================================
         1. TOP POS TERMINAL BAR
         ============================================== -->
    <header class="bg-slate-900 border-b border-slate-800 px-5 py-3 flex items-center justify-between shrink-0 shadow-md">
        <!-- POS Logo & Status -->
        <div class="flex items-center space-x-3">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-400 flex items-center justify-center font-black text-slate-950 text-xl shadow-lg">
                ₹
            </div>
            <div>
                <h1 class="text-base font-extrabold tracking-wider uppercase text-white leading-tight">Store Billing POS</h1>
                <span class="text-[10px] font-mono uppercase bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 px-2 py-0.5 rounded-full font-bold">
                    Terminal #01 &bull; Ready
                </span>
            </div>
        </div>

        <!-- Terminal Actions -->
        <div class="flex items-center space-x-3">
            <!-- Low Stock Indicator -->
            <button @click="showLowStockDrawer = !showLowStockDrawer" 
                    class="flex items-center space-x-2 px-3 py-1.5 rounded-xl border transition text-xs font-semibold shadow-xs"
                    :class="lowStockList.length > 0 ? 'bg-amber-500/10 border-amber-500/30 text-amber-300 hover:bg-amber-500/20' : 'bg-slate-800 border-slate-700 text-slate-400'">
                <span class="relative flex h-2 w-2">
                    <span x-show="lowStockList.length > 0" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2" :class="lowStockList.length > 0 ? 'bg-amber-400' : 'bg-emerald-500'"></span>
                </span>
                <span x-text="lowStockList.length > 0 ? lowStockList.length + ' Low Stock Alert(s)' : 'Stock Healthy'"></span>
            </button>

            <!-- Order History -->
            <button @click="openCustomerHistoryModal()" 
                    class="flex items-center space-x-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 hover:border-slate-600 px-3.5 py-1.5 rounded-xl text-xs font-semibold transition shadow-sm">
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Order History</span>
            </button>

            <!-- Reset Sale -->
            <button @click="resetSale()" 
                    title="Start fresh ticket"
                    class="bg-slate-800/80 hover:bg-rose-950/40 text-slate-400 hover:text-rose-300 border border-slate-700 hover:border-rose-500/30 px-3.5 py-1.5 rounded-xl text-xs font-semibold transition flex items-center space-x-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <span>New Sale</span>
            </button>
        </div>
    </header>

    <!-- Floating Toast Notification -->
    <div x-show="errorMessage" 
         x-transition 
         class="fixed top-16 right-4 z-50 bg-rose-600 text-white text-xs px-4 py-2.5 rounded-xl shadow-2xl flex items-center space-x-2 border border-rose-500 font-semibold">
        <span>⚠️</span>
        <span x-text="errorMessage"></span>
        <button @click="errorMessage = ''" class="ml-2 font-bold text-white hover:text-rose-200">&times;</button>
    </div>

    <!-- ==============================================
         2. MAIN POS WORKSPACE: 2-COLUMN APP LAYOUT
         ============================================== -->
    <div class="flex-1 flex overflow-hidden">

        <!-- ==============================================
             LEFT WORKSPACE: PRODUCT CATALOG & TOUCH TILES (60%)
             ============================================== -->
        <section class="flex-1 flex flex-col bg-slate-950 border-r border-slate-800 overflow-hidden">
            
            <!-- Catalog Header with Search & Filter Tabs -->
            <div class="p-4 bg-slate-900/60 border-b border-slate-800/80 flex flex-wrap items-center justify-between gap-3 shrink-0">
                <!-- Search Bar -->
                <div class="relative flex-1 min-w-[240px]">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" 
                           x-model="productSearch" 
                           placeholder="search product by name, code... (e.g. Rice, PRD-001)" 
                           class="w-full text-xs bg-slate-800/90 text-slate-100 placeholder-slate-400 pl-10 pr-8 py-2.5 rounded-xl border border-slate-700 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none transition font-medium">
                    <button x-show="productSearch.length > 0" 
                            @click="productSearch = ''" 
                            class="absolute right-3 top-2.5 text-slate-400 hover:text-white text-xs">&times;</button>
                </div>

                <!-- Catalog Filter Tabs -->
                <div class="flex items-center space-x-1.5 bg-slate-800/80 p-1 rounded-xl border border-slate-700/60 text-xs font-semibold">
                    <button @click="catalogFilter = 'all'" 
                            :class="catalogFilter === 'all' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-400 hover:text-slate-200'"
                            class="px-3 py-1.5 rounded-lg transition">
                        All Items
                    </button>
                    <button @click="catalogFilter = 'instock'" 
                            :class="catalogFilter === 'instock' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-400 hover:text-slate-200'"
                            class="px-3 py-1.5 rounded-lg transition">
                        In Stock
                    </button>
                    <button @click="catalogFilter = 'lowstock'" 
                            :class="catalogFilter === 'lowstock' ? 'bg-amber-600 text-white shadow-xs' : 'text-slate-400 hover:text-slate-200'"
                            class="px-3 py-1.5 rounded-lg transition flex items-center space-x-1">
                        <span>Low Stock</span>
                        <span class="bg-black/30 px-1.5 py-0.2 rounded-full text-[10px]" x-text="lowStockList.length"></span>
                    </button>
                </div>
            </div>

            <!-- Product Grid (Touch POS Tiles) -->
            <div class="flex-1 overflow-y-auto p-4">
                <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3.5">
                    <template x-for="product in filteredProducts" :key="product.id">
                        <button type="button" 
                                @click="addToCart(product)" 
                                :disabled="product.stock <= 0" 
                                class="group relative flex flex-col justify-between text-left p-3.5 rounded-2xl border transition shadow-sm active:scale-97 cursor-pointer"
                                :class="product.stock <= 0 
                                    ? 'bg-slate-900/40 border-slate-800/50 opacity-40 cursor-not-allowed' 
                                    : (isProductInCart(product.id) 
                                        ? 'bg-slate-900 border-emerald-500/80 ring-2 ring-emerald-500/20' 
                                        : 'bg-slate-900/90 border-slate-800 hover:border-slate-700 hover:bg-slate-800/80')">
                            
                            <!-- Card Top: Code & Stock Badge -->
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-mono text-[10px] text-slate-400 bg-slate-800 px-2 py-0.5 rounded-md" x-text="product.code"></span>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                                      :class="product.stock <= 0 
                                          ? 'bg-rose-500/20 text-rose-400 border border-rose-500/30' 
                                          : (product.stock <= 10 
                                              ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30' 
                                              : 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30')"
                                      x-text="product.stock <= 0 ? 'Out of Stock' : product.stock + ' in stock'">
                                </span>
                            </div>

                            <!-- Card Middle: Name -->
                            <div class="my-1">
                                <h3 class="text-sm font-bold text-white group-hover:text-emerald-400 transition line-clamp-1" x-text="product.name"></h3>
                                <p class="text-[11px] text-slate-400 mt-0.5">Tax rate: <span class="font-mono text-slate-300" x-text="parseFloat(product.tax_percentage).toFixed(1) + '%'"></span></p>
                            </div>

                            <!-- Card Bottom: Price & Add Icon -->
                            <div class="mt-3 pt-2 border-t border-slate-800/70 flex items-center justify-between">
                                <span class="text-base font-extrabold font-mono text-emerald-400">
                                    ₹<span x-text="parseFloat(product.price).toFixed(2)"></span>
                                </span>
                                <div class="w-7 h-7 rounded-xl bg-slate-800 group-hover:bg-emerald-500 text-slate-300 group-hover:text-slate-950 flex items-center justify-center font-bold text-sm transition">
                                    +
                                </div>
                            </div>

                            <!-- Quantity in Cart Pill Indicator -->
                            <div x-show="getCartItemQuantity(product.id) > 0" 
                                 class="absolute -top-2 -right-2 bg-emerald-500 text-slate-950 text-xs font-black w-6 h-6 rounded-full flex items-center justify-center shadow-md border-2 border-slate-950">
                                <span x-text="getCartItemQuantity(product.id)"></span>
                            </div>
                        </button>
                    </template>
                </div>

                <!-- Empty Search State -->
                <div x-show="filteredProducts.length === 0" class="h-64 flex flex-col items-center justify-center text-center p-6 text-slate-500">
                    <svg class="w-12 h-12 text-slate-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <p class="text-sm font-semibold text-slate-400">No products matching your search</p>
                    <p class="text-xs text-slate-500 mt-1">Try searching for rice, oil, or sugar</p>
                </div>
            </div>
        </section>

        <!-- ==============================================
             RIGHT WORKSPACE: POS REGISTER TICKET & TENDER (40%)
             ============================================== -->
        <aside class="w-full md:w-[480px] lg:w-[520px] bg-slate-900 flex flex-col overflow-hidden shrink-0 shadow-2xl">
            
            <!-- 1. Customer Header Strip -->
            <div class="p-3.5 bg-slate-900 border-b border-slate-800 relative" @click.away="showSuggestions = false">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Customer</span>
                    <span x-show="customerFound" class="text-[10px] font-semibold text-emerald-400">
                        ✓ Verified on file
                    </span>
                    <span x-show="!customerFound" class="text-[10px] text-slate-500">
                        Type 2+ letters to search
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-2 relative">
                    <!-- Email Input -->
                    <div>
                        <input type="email" 
                               x-model="customerEmail" 
                               @input.debounce.150ms="onCustomerInput('email')" 
                               @focus="if(customerEmail.length >= 2) onCustomerInput('email')"
                               placeholder="Email (e.g. arun...)" 
                               class="w-full text-xs bg-slate-800 text-slate-100 placeholder-slate-500 px-3 py-2 rounded-xl border border-slate-700 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none transition font-medium">
                    </div>

                    <!-- Name Input -->
                    <div>
                        <input type="text" 
                               x-model="customerName" 
                               @input.debounce.150ms="onCustomerInput('name')" 
                               @focus="if(customerName.length >= 2) onCustomerInput('name')"
                               placeholder="Name (e.g. Arun...)" 
                               class="w-full text-xs bg-slate-800 text-slate-100 placeholder-slate-500 px-3 py-2 rounded-xl border border-slate-700 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none transition font-medium">
                    </div>

                    <!-- Autocomplete Popover (spans across both inputs) -->
                    <div x-show="showSuggestions && customerSuggestions.length > 0" 
                         x-transition 
                         class="absolute left-0 right-0 top-full mt-1.5 bg-slate-800 border border-slate-700 rounded-xl shadow-2xl z-50 overflow-hidden divide-y divide-slate-700/60 max-h-52 overflow-y-auto">
                        <div class="px-3 py-1.5 bg-slate-950 text-[10px] font-bold text-slate-400 uppercase tracking-wider flex justify-between items-center">
                            <span>Matching Customers</span>
                            <span class="text-[10px] text-slate-500">Click to select</span>
                        </div>
                        <template x-for="cust in customerSuggestions" :key="cust.id">
                            <button type="button" 
                                    @click="selectCustomer(cust)" 
                                    class="w-full text-left px-3.5 py-2.5 hover:bg-slate-700/80 transition flex items-center justify-between text-xs group">
                                <div>
                                    <div class="font-bold text-white group-hover:text-emerald-300" x-text="cust.name"></div>
                                    <div class="text-[10px] text-slate-400 font-mono" x-text="cust.email"></div>
                                </div>
                                <span class="text-[10px] text-emerald-400 bg-emerald-500/20 px-2 py-0.5 rounded font-semibold">Select</span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <!-- 2. Active Order Ticket / Lines (Scrollable) -->
            <div class="flex-1 overflow-y-auto p-3.5 space-y-2">
                <!-- Empty Cart State -->
                <div x-show="cartItems.length === 0" class="h-full flex flex-col items-center justify-center text-center p-6 text-slate-500">
                    <div class="w-14 h-14 rounded-2xl bg-slate-800 flex items-center justify-center text-2xl mb-3 text-slate-400">
                        🛒
                    </div>
                    <p class="text-sm font-bold text-slate-300">Ticket is Empty</p>
                    <p class="text-xs text-slate-500 mt-1 max-w-[220px]">Click any product tile on the left to start billing an order.</p>
                </div>

                <!-- Cart Line Items -->
                <template x-for="(item, index) in cartItems" :key="item.product_id">
                    <div class="bg-slate-800/80 border border-slate-700/80 hover:border-slate-600 rounded-2xl p-3 flex items-center justify-between gap-2 transition shadow-xs">
                        <!-- Product Title & Unit Price -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center space-x-1.5">
                                <h4 class="text-xs font-bold text-white truncate" x-text="item.product.name"></h4>
                                <span class="text-[9px] font-mono text-slate-400 bg-slate-900 px-1.5 py-0.5 rounded" x-text="item.product.code"></span>
                            </div>
                            <div class="text-[11px] text-slate-400 mt-0.5 flex items-center space-x-2">
                                <span>₹<span x-text="parseFloat(item.product.price).toFixed(2)"></span></span>
                                <span>&bull;</span>
                                <span class="text-slate-400 font-mono">Tax: <span x-text="parseFloat(item.product.tax_percentage).toFixed(1) + '%'"></span></span>
                            </div>
                        </div>

                        <!-- Quantity Stepper -->
                        <div class="flex items-center space-x-1 bg-slate-900 p-1 rounded-xl border border-slate-700">
                            <button type="button" 
                                    @click="decrementItem(index)" 
                                    class="w-7 h-7 flex items-center justify-center rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 active:scale-95 transition font-bold text-sm">
                                &minus;
                            </button>
                            <span class="w-8 text-center text-xs font-mono font-bold text-white" x-text="item.quantity"></span>
                            <button type="button" 
                                    @click="incrementItem(index)" 
                                    :disabled="item.quantity >= item.product.stock"
                                    class="w-7 h-7 flex items-center justify-center rounded-lg bg-slate-800 hover:bg-slate-700 disabled:opacity-30 text-slate-200 active:scale-95 transition font-bold text-sm">
                                &plus;
                            </button>
                        </div>

                        <!-- Line Total (Net + Tax) -->
                        <div class="text-right min-w-[70px]">
                            <div class="text-xs font-mono font-bold text-emerald-400">
                                ₹<span x-text="calculateCartLineTotal(item).toFixed(2)"></span>
                            </div>
                            <button type="button" 
                                    @click="removeCartItem(index)" 
                                    class="text-[10px] text-slate-500 hover:text-rose-400 transition font-medium mt-0.5">
                                Remove
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <!-- 3. Totals & LCD Register Display -->
            <div class="p-4 bg-slate-950 border-t border-slate-800 space-y-3 shrink-0">
                
                <!-- Financial Summary Grid -->
                <div class="space-y-1.5 text-xs text-slate-400">
                    <div class="flex justify-between items-center">
                        <span>Subtotal (Net):</span>
                        <span class="font-mono text-slate-200 font-semibold">₹<span x-text="subtotal.toFixed(2)"></span></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span>Tax Amount:</span>
                        <span class="font-mono text-slate-200 font-semibold">₹<span x-text="taxAmount.toFixed(2)"></span></span>
                    </div>
                    <div class="flex justify-between items-center pt-2 border-t border-slate-800/80">
                        <span class="text-sm font-bold text-white uppercase tracking-wider">Total Payable:</span>
                        <span class="text-2xl font-mono font-black text-emerald-400">₹<span x-text="grandTotal.toFixed(2)"></span></span>
                    </div>
                </div>

                <!-- 4. Cash Tender & Change Station -->
                <div class="bg-slate-900 p-3 rounded-2xl border border-slate-800 space-y-2.5">
                    <div class="flex items-center justify-between text-xs">
                        <label class="font-bold text-slate-300">Amount Tendered (₹)</label>
                        <!-- Exact Total Quick Button -->
                        <button type="button" 
                                @click="setExactTender()" 
                                class="text-[11px] font-semibold text-emerald-400 hover:text-emerald-300 bg-emerald-500/10 px-2 py-0.5 rounded-lg border border-emerald-500/20">
                            Reset to Exact (₹<span x-text="grandTotal.toFixed(2)"></span>)
                        </button>
                    </div>

                    <!-- Tender Input & Fast Denominations -->
                    <div class="flex items-center space-x-2">
                        <div class="relative flex-1">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 font-bold text-sm">₹</span>
                            <input type="number" 
                                   step="0.01" 
                                   min="0" 
                                   x-model.number="amountGiven" 
                                   @input="isManualTender = true; recalculateBalance()" 
                                   placeholder="0.00" 
                                   class="w-full text-base font-mono font-extrabold bg-slate-950 text-white pl-8 pr-3 py-2 rounded-xl border border-slate-700 focus:border-emerald-500 outline-none">
                        </div>

                        <!-- Fast Cash Pills -->
                        <div class="flex items-center space-x-1 text-xs">
                            <button type="button" @click="setFastCash(200)" class="px-2 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl font-bold font-mono transition border border-slate-700">₹200</button>
                            <button type="button" @click="setFastCash(500)" class="px-2 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl font-bold font-mono transition border border-slate-700">₹500</button>
                            <button type="button" @click="setFastCash(2000)" class="px-2 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl font-bold font-mono transition border border-slate-700">₹2k</button>
                        </div>
                    </div>

                    <!-- Change / Denomination Display -->
                    <div class="bg-slate-950 p-2.5 rounded-xl border border-slate-800 flex items-center justify-between text-xs">
                        <div>
                            <span class="text-[10px] text-slate-400 block font-semibold">Change to Return</span>
                            <span class="font-mono font-black text-sm" :class="balanceReturn >= 0 ? 'text-emerald-400' : 'text-rose-400'">
                                ₹<span x-text="balanceReturn >= 0 ? balanceReturn.toFixed(2) : '0.00'"></span>
                            </span>
                        </div>

                        <!-- Denomination Badges -->
                        <div class="flex flex-wrap gap-1 justify-end max-w-[200px]" x-show="balanceReturn > 0">
                            <template x-for="chip in getDenominationChips(balanceReturn)" :key="chip">
                                <span class="font-mono text-[10px] bg-slate-800 text-amber-300 border border-amber-500/20 px-1.5 py-0.5 rounded font-semibold" x-text="chip"></span>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- 5. Prominent POS CHARGE / GENERATE BILL BUTTON -->
                <button type="button" 
                        @click="generateBill()" 
                        :disabled="isSubmitting || cartItems.length === 0 || !customerEmail || grandTotal <= 0" 
                        class="w-full py-3.5 px-4 bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 disabled:from-slate-800 disabled:to-slate-800 disabled:opacity-40 disabled:cursor-not-allowed text-slate-950 font-black rounded-2xl shadow-lg transition flex items-center justify-center space-x-2 text-sm tracking-wider uppercase active:scale-98">
                    <svg x-show="isSubmitting" class="animate-spin -ml-1 mr-2 h-5 w-5 text-slate-950" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span x-text="isSubmitting ? 'Processing...' : 'Charge & Generate Bill'"></span>
                </button>
            </div>
        </aside>

    </div>

    <!-- ==============================================
         3. LOW STOCK NOTIFICATION DRAWER
         ============================================== -->
    <div x-show="showLowStockDrawer" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-x-full"
         x-transition:enter-end="opacity-100 translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-x-0"
         x-transition:leave-end="opacity-0 translate-x-full"
         class="fixed inset-y-0 right-0 z-50 w-80 bg-slate-900 border-l border-slate-800 shadow-2xl p-5 flex flex-col">
        
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
            <div class="flex items-center space-x-2">
                <span class="text-amber-400 text-lg">⚠️</span>
                <div>
                    <h3 class="text-xs font-bold text-white uppercase tracking-wider">Low Stock Inventory</h3>
                    <p class="text-[10px] text-slate-400">Products requiring reorder</p>
                </div>
            </div>
            <button @click="showLowStockDrawer = false" class="text-slate-400 hover:text-white text-lg font-bold">&times;</button>
        </div>

        <div class="my-3 flex items-center justify-between text-xs bg-slate-950 p-2.5 rounded-xl border border-slate-800">
            <span class="text-slate-400 font-medium">Alert Threshold:</span>
            <div class="flex items-center space-x-1">
                <span class="text-slate-400 font-semibold">&lt;</span>
                <input type="number" 
                       min="1" 
                       x-model.number="lowStockThreshold" 
                       @change="fetchLowStock()" 
                       class="w-12 text-center text-xs font-mono font-bold bg-slate-800 text-white rounded-lg py-1 border border-slate-700 outline-none">
                <button @click="fetchLowStock()" class="p-1 text-slate-400 hover:text-white">🔄</button>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto space-y-2">
            <template x-for="item in lowStockList" :key="item.id">
                <div class="bg-slate-950 border border-slate-800 rounded-xl p-3 flex items-center justify-between text-xs">
                    <div>
                        <div class="font-bold text-white" x-text="item.name"></div>
                        <div class="text-[10px] text-slate-400 font-mono" x-text="item.code"></div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="bg-amber-500/20 text-amber-300 border border-amber-500/30 px-2 py-0.5 rounded-md font-mono font-extrabold text-[11px]" x-text="item.stock + ' left'"></span>
                        <button type="button" 
                                @click="addToCart(item)" 
                                :disabled="item.stock <= 0"
                                class="text-[10px] bg-slate-800 hover:bg-slate-700 text-slate-200 px-2 py-1 rounded-md font-bold transition">
                            + Add
                        </button>
                    </div>
                </div>
            </template>
            <div x-show="lowStockList.length === 0" class="text-center py-8 text-xs text-slate-500">
                All products have healthy inventory!
            </div>
        </div>
    </div>

    <!-- ==============================================
         4. RECEIPT INVOICE MODAL (THERMAL POS FORMAT)
         ============================================== -->
    <div x-show="showReceiptModal" 
         x-transition 
         class="fixed inset-0 z-50 overflow-y-auto bg-black/80 backdrop-blur-xs flex items-center justify-center p-4">
        
        <div @click.away="showReceiptModal = false" 
             id="printable-receipt"
             class="bg-white text-slate-900 rounded-3xl shadow-2xl max-w-sm w-full overflow-hidden border border-slate-200">
            
            <div class="bg-slate-950 text-white p-6 text-center">
                <div class="w-10 h-10 bg-emerald-500/20 text-emerald-400 rounded-xl flex items-center justify-center mx-auto mb-2 text-xl font-bold">
                    ✓
                </div>
                <h3 class="text-base font-extrabold tracking-wider uppercase">Store Billing Receipt</h3>
                <p class="text-[11px] text-slate-400 mt-0.5">Terminal #01 &bull; Retail Counter POS</p>
            </div>

            <div class="p-5 space-y-3.5 text-xs" x-show="lastOrder">
                <!-- Receipt Metadata -->
                <div class="flex justify-between items-center text-[11px] text-slate-500 border-b border-dashed border-slate-300 pb-2.5">
                    <div>
                        <strong class="text-slate-900 block" x-text="'Receipt #' + lastOrder?.id"></strong>
                        <span x-text="new Date().toLocaleString()"></span>
                    </div>
                    <div class="text-right">
                        <strong class="text-slate-900 block" x-text="lastOrder?.customer?.name"></strong>
                        <span class="font-mono text-[10px]" x-text="lastOrder?.customer?.email"></span>
                    </div>
                </div>

                <!-- Purchased Items -->
                <div class="divide-y divide-slate-100 max-h-48 overflow-y-auto">
                    <template x-for="item in lastOrder?.items" :key="item.id">
                        <div class="py-2 flex justify-between items-center">
                            <div>
                                <span class="font-bold text-slate-900" x-text="item.product?.name || 'Item #' + item.product_id"></span>
                                <span class="text-[10px] text-slate-500 block" x-text="item.quantity + ' × ₹' + parseFloat(item.unit_price).toFixed(2) + ' (+ ' + parseFloat(item.tax_percentage).toFixed(1) + '% Tax)'"></span>
                            </div>
                            <span class="font-mono font-bold text-slate-900">₹<span x-text="parseFloat(item.total).toFixed(2)"></span></span>
                        </div>
                    </template>
                </div>

                <!-- Totals -->
                <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200 space-y-1.5 text-xs">
                    <div class="flex justify-between text-slate-600">
                        <span>Subtotal:</span>
                        <span class="font-mono font-semibold">₹<span x-text="parseFloat(lastOrder?.subtotal || 0).toFixed(2)"></span></span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Tax Amount:</span>
                        <span class="font-mono font-semibold">₹<span x-text="parseFloat(lastOrder?.tax_amount || 0).toFixed(2)"></span></span>
                    </div>
                    <div class="flex justify-between font-black text-slate-950 border-t border-slate-200 pt-2 text-sm">
                        <span>Grand Total:</span>
                        <span class="font-mono text-emerald-700">₹<span x-text="parseFloat(lastOrder?.grand_total || 0).toFixed(2)"></span></span>
                    </div>
                    <div class="flex justify-between text-slate-600 pt-1 text-[11px]" x-show="amountGiven > 0">
                        <span>Cash Tendered:</span>
                        <span class="font-mono font-semibold">₹<span x-text="amountGiven.toFixed(2)"></span></span>
                    </div>
                    <div class="flex justify-between text-emerald-700 font-bold text-[11px]" x-show="balanceReturn >= 0">
                        <span>Change Returned:</span>
                        <span class="font-mono font-black">₹<span x-text="balanceReturn.toFixed(2)"></span></span>
                    </div>
                </div>

                <p class="text-center text-slate-400 text-[10px] pt-1">
                    Thank you for your purchase!
                </p>
            </div>

            <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end space-x-2">
                <button type="button" @click="window.print()" class="px-4 py-2 border border-slate-300 bg-white text-slate-700 hover:bg-slate-100 rounded-xl text-xs font-bold transition">
                    🖨️ Print
                </button>
                <button type="button" @click="resetSale(); showReceiptModal = false;" class="px-4 py-2 bg-slate-950 hover:bg-slate-900 text-white rounded-xl text-xs font-bold transition">
                    Next Sale (Esc)
                </button>
            </div>
        </div>
    </div>

    <!-- ==============================================
         5. CUSTOMER HISTORY MODAL
         ============================================== -->
    <div x-show="showHistoryModal" 
         x-transition 
         class="fixed inset-0 z-50 overflow-y-auto bg-black/80 backdrop-blur-xs flex items-center justify-center p-4">
        
        <div @click.away="showHistoryModal = false" class="bg-slate-900 text-white rounded-3xl shadow-2xl max-w-xl w-full overflow-hidden border border-slate-800">
            <div class="p-5 border-b border-slate-800 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold">Customer Order History</h3>
                    <p class="text-xs text-slate-400">Search customer past purchases</p>
                </div>
                <button @click="showHistoryModal = false" class="text-slate-400 hover:text-white text-xl font-bold">&times;</button>
            </div>

            <div class="p-5 space-y-4">
                <div class="flex space-x-2">
                    <input type="email" 
                           x-model="historyEmail" 
                           @keydown.enter.prevent="fetchCustomerHistory()"
                           placeholder="Customer email (e.g. arun@example.com)" 
                           class="flex-1 text-xs bg-slate-800 text-white px-3.5 py-2.5 rounded-xl border border-slate-700 outline-none focus:border-emerald-500 font-medium">
                    <button type="button" 
                            @click="fetchCustomerHistory()" 
                            class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-slate-950 font-black text-xs rounded-xl transition">
                        Fetch
                    </button>
                </div>

                <div x-show="isFetchingHistory" class="text-center py-6 text-xs text-slate-400">
                    <span class="inline-block animate-spin mr-2">🔄</span> Retrieving orders...
                </div>

                <div x-show="!isFetchingHistory && historyCustomer" class="space-y-3">
                    <div class="text-xs text-slate-300 bg-slate-950 p-3 rounded-xl border border-slate-800 flex justify-between items-center">
                        <div>
                            Customer: <strong class="text-white font-bold" x-text="historyCustomer?.name"></strong> (<span x-text="historyCustomer?.email"></span>)
                        </div>
                        <span class="bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 px-2 py-0.5 rounded-full font-bold text-[10px]" x-text="(historyCustomer?.orders?.length || 0) + ' Order(s)'"></span>
                    </div>

                    <div class="max-h-64 overflow-y-auto space-y-2 pr-1">
                        <template x-for="ord in historyCustomer?.orders" :key="ord.id">
                            <div class="p-3 border border-slate-800 rounded-xl text-xs space-y-1 bg-slate-950">
                                <div class="flex justify-between font-bold text-white">
                                    <span x-text="'Order #' + ord.id"></span>
                                    <span class="font-mono text-emerald-400 font-extrabold" x-text="'₹' + parseFloat(ord.grand_total).toFixed(2)"></span>
                                </div>
                                <div class="text-slate-400 text-[10px]" x-text="'Placed: ' + new Date(ord.created_at).toLocaleString()"></div>
                                <div class="text-slate-400 text-[10px] pt-1 border-t border-slate-800/80 flex flex-wrap gap-1">
                                    <template x-for="it in ord.items" :key="it.id">
                                        <span class="bg-slate-800 text-slate-300 px-1.5 py-0.5 rounded font-mono text-[9px]" 
                                              x-text="(it.product?.name || 'Item') + ' (x' + it.quantity + ')'">
                                        </span>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div class="p-4 bg-slate-950 border-t border-slate-800 text-right">
                <button type="button" @click="showHistoryModal = false" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-bold transition">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- ==============================================
         6. CLIENT-SIDE POS CONTROLLER ENGINE
         ============================================== -->
    <script>
        function posTerminal() {
            return {
                customerEmail: '',
                customerName: '',
                customerFound: false,
                customerSuggestions: [],
                showSuggestions: false,

                productSearch: '',
                catalogFilter: 'all',

                amountGiven: 0,
                isManualTender: false,
                lowStockThreshold: 10,

                availableProducts: [],
                lowStockList: [],

                // Cart items: array of { product_id, product, quantity }
                cartItems: [],

                subtotal: 0,
                taxAmount: 0,
                grandTotal: 0,
                balanceReturn: 0,

                isSubmitting: false,
                errorMessage: '',
                successMessage: '',

                showReceiptModal: false,
                lastOrder: null,

                showHistoryModal: false,
                historyEmail: '',
                historyCustomer: null,
                isFetchingHistory: false,

                showLowStockDrawer: false,

                init() {
                    this.fetchProducts();
                    this.fetchLowStock();
                },

                isEmailValid(email) {
                    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
                },

                get filteredProducts() {
                    let list = this.availableProducts;
                    if (this.catalogFilter === 'instock') {
                        list = list.filter(p => p.stock > 0);
                    } else if (this.catalogFilter === 'lowstock') {
                        list = list.filter(p => p.stock < this.lowStockThreshold);
                    }

                    if (this.productSearch.trim().length > 0) {
                        const q = this.productSearch.toLowerCase();
                        list = list.filter(p => 
                            p.name.toLowerCase().includes(q) || 
                            p.code.toLowerCase().includes(q)
                        );
                    }
                    return list;
                },

                async onCustomerInput(source) {
                    const query = source === 'email' ? this.customerEmail : this.customerName;

                    if (!query || query.trim().length < 2) {
                        this.customerSuggestions = [];
                        this.showSuggestions = false;
                        if (!this.customerEmail) this.customerFound = false;
                        return;
                    }

                    try {
                        const res = await fetch(`/api/customers/search?q=${encodeURIComponent(query.trim())}`);
                        const json = await res.json();
                        if (json.success && json.data) {
                            this.customerSuggestions = json.data;
                            this.showSuggestions = this.customerSuggestions.length > 0;

                            // If exact email match exists, auto-fill name and mark verified
                            if (source === 'email' && this.customerEmail) {
                                const match = this.customerSuggestions.find(c => c.email.toLowerCase() === this.customerEmail.toLowerCase());
                                if (match) {
                                    this.customerName = match.name;
                                    this.customerFound = true;
                                } else {
                                    this.customerFound = false;
                                }
                            }
                        }
                    } catch (e) {
                        console.error('Customer search error', e);
                    }
                },

                selectCustomer(cust) {
                    this.customerEmail = cust.email;
                    this.customerName = cust.name;
                    this.customerFound = true;
                    this.showSuggestions = false;
                    this.customerSuggestions = [];
                },

                async fetchProducts() {
                    try {
                        const res = await fetch('/api/products');
                        const json = await res.json();
                        if (json.success && json.data) {
                            this.availableProducts = json.data;
                            this.recalculate();
                        }
                    } catch (e) {
                        console.error('Error fetching products', e);
                    }
                },

                async fetchLowStock() {
                    try {
                        const res = await fetch(`/api/products/low-stock?threshold=${this.lowStockThreshold}`);
                        const json = await res.json();
                        if (json.success && json.data) {
                            this.lowStockList = json.data;
                        }
                    } catch (e) {
                        console.error('Error fetching low stock', e);
                    }
                },

                // Cart Operations
                addToCart(product) {
                    if (product.stock <= 0) return;
                    const existing = this.cartItems.find(i => i.product_id === product.id);
                    if (existing) {
                        if (existing.quantity < product.stock) {
                            existing.quantity++;
                        }
                    } else {
                        this.cartItems.push({
                            product_id: product.id,
                            product: product,
                            quantity: 1
                        });
                    }
                    this.recalculate();
                },

                incrementItem(index) {
                    const item = this.cartItems[index];
                    if (item.quantity < item.product.stock) {
                        item.quantity++;
                        this.recalculate();
                    }
                },

                decrementItem(index) {
                    const item = this.cartItems[index];
                    if (item.quantity > 1) {
                        item.quantity--;
                    } else {
                        this.cartItems.splice(index, 1);
                    }
                    this.recalculate();
                },

                removeCartItem(index) {
                    this.cartItems.splice(index, 1);
                    this.recalculate();
                },

                isProductInCart(productId) {
                    return this.cartItems.some(i => i.product_id === productId);
                },

                getCartItemQuantity(productId) {
                    const it = this.cartItems.find(i => i.product_id === productId);
                    return it ? it.quantity : 0;
                },

                calculateCartLineTotal(item) {
                    const price = parseFloat(item.product.price) || 0;
                    const taxRate = parseFloat(item.product.tax_percentage) || 0;
                    const qty = parseInt(item.quantity) || 0;
                    const sub = price * qty;
                    return sub + (sub * (taxRate / 100));
                },

                recalculate() {
                    let sub = 0;
                    let tax = 0;

                    this.cartItems.forEach(item => {
                        const price = parseFloat(item.product.price) || 0;
                        const taxRate = parseFloat(item.product.tax_percentage) || 0;
                        const qty = parseInt(item.quantity) || 0;

                        const lineSub = price * qty;
                        const lineTax = lineSub * (taxRate / 100);

                        sub += lineSub;
                        tax += lineTax;
                    });

                    this.subtotal = sub;
                    this.taxAmount = tax;
                    this.grandTotal = sub + tax;

                    if (this.cartItems.length === 0) {
                        this.amountGiven = 0;
                        this.isManualTender = false;
                    } else if (!this.isManualTender) {
                        this.amountGiven = parseFloat(this.grandTotal.toFixed(2));
                    }

                    this.recalculateBalance();
                },

                recalculateBalance() {
                    if (this.amountGiven >= this.grandTotal) {
                        this.balanceReturn = this.amountGiven - this.grandTotal;
                    } else {
                        this.balanceReturn = -(this.grandTotal - this.amountGiven);
                    }
                },

                setExactTender() {
                    this.amountGiven = parseFloat(this.grandTotal.toFixed(2));
                    this.isManualTender = false;
                    this.recalculateBalance();
                },

                setFastCash(amount) {
                    this.amountGiven = amount;
                    this.isManualTender = true;
                    this.recalculateBalance();
                },

                getDenominationChips(amount) {
                    if (amount <= 0) return [];
                    let remaining = Math.round(amount * 100) / 100;
                    const denominations = [500, 200, 100, 50, 20, 10, 5, 2, 1, 0.50];
                    const chips = [];

                    for (const d of denominations) {
                        if (remaining >= d) {
                            const count = Math.floor(remaining / d);
                            if (count > 0) {
                                chips.push(`${count}×₹${d}`);
                                remaining = Math.round((remaining - (count * d)) * 100) / 100;
                            }
                        }
                    }

                    if (remaining > 0.01) {
                        chips.push(`₹${remaining.toFixed(2)} Coins`);
                    }

                    return chips;
                },

                notifyError(msg) {
                    this.errorMessage = msg;
                    setTimeout(() => { this.errorMessage = ''; }, 3500);
                },

                async generateBill() {
                    if (!this.customerEmail || !this.customerName) {
                        this.notifyError('Please provide customer email and name.');
                        return;
                    }

                    if (this.cartItems.length === 0) {
                        this.notifyError('Ticket is empty! Select products on the left.');
                        return;
                    }

                    // Stock check
                    for (const it of this.cartItems) {
                        if (it.quantity > it.product.stock) {
                            this.notifyError(`Insufficient stock for ${it.product.name}. Available: ${it.product.stock}`);
                            return;
                        }
                    }

                    this.isSubmitting = true;

                    try {
                        const payload = {
                            customer_name: this.customerName,
                            customer_email: this.customerEmail,
                            items: this.cartItems.map(i => ({
                                product_id: parseInt(i.product_id),
                                quantity: parseInt(i.quantity)
                            }))
                        };

                        const response = await fetch('/api/orders', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                            },
                            body: JSON.stringify(payload)
                        });

                        const result = await response.json();

                        if (!response.ok) {
                            if (result.errors) {
                                const msgs = Object.values(result.errors).flat().join(' ');
                                throw new Error(msgs);
                            }
                            throw new Error(result.message || 'Failed to place order.');
                        }

                        this.lastOrder = result.data;
                        this.showReceiptModal = true;

                        await this.fetchProducts();
                        await this.fetchLowStock();

                    } catch (err) {
                        this.notifyError(err.message || 'Order failed.');
                    } finally {
                        this.isSubmitting = false;
                    }
                },

                resetSale() {
                    this.customerEmail = '';
                    this.customerName = '';
                    this.customerFound = false;
                    this.customerSuggestions = [];
                    this.showSuggestions = false;
                    this.cartItems = [];
                    this.amountGiven = 0;
                    this.isManualTender = false;
                    this.recalculate();
                },

                openCustomerHistoryModal() {
                    this.historyEmail = this.customerEmail;
                    this.showHistoryModal = true;
                    if (this.historyEmail) {
                        this.fetchCustomerHistory();
                    }
                },

                async fetchCustomerHistory() {
                    if (!this.historyEmail) return;
                    this.isFetchingHistory = true;
                    this.historyCustomer = null;
                    try {
                        const res = await fetch(`/api/customer/orders?email=${encodeURIComponent(this.historyEmail)}`);
                        const json = await res.json();
                        if (json.success && json.data) {
                            this.historyCustomer = json.data;
                        } else {
                            this.historyCustomer = null;
                        }
                    } catch (e) {
                        console.error('History fetch error', e);
                    } finally {
                        this.isFetchingHistory = false;
                    }
                }
            };
        }
    </script>
</body>
</html>
