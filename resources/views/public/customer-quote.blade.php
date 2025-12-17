<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quote #{{ $quote->quote_number }} - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full">
    <div class="min-h-full">
        <!-- Header -->
        <header class="bg-white shadow-sm">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <div class="md:flex md:items-center md:justify-between">
                    <div class="min-w-0 flex-1">
                        <h1 class="text-3xl font-bold tracking-tight text-gray-900">
                            Quote #{{ $quote->quote_number }}
                        </h1>
                        <p class="mt-1 text-sm text-gray-500">
                            For: <span class="font-medium text-gray-900">{{ $customer->name }}</span>
                        </p>
                    </div>
                    <div class="mt-4 flex md:ml-4 md:mt-0">
                        <span class="inline-flex items-center rounded-md px-3 py-2 text-sm font-semibold 
                            @if($quote->status === 'accepted') bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20
                            @elseif($quote->status === 'viewed') bg-yellow-50 text-yellow-800 ring-1 ring-inset ring-yellow-600/20
                            @elseif($quote->status === 'sent') bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-700/10
                            @else bg-gray-50 text-gray-600 ring-1 ring-inset ring-gray-500/10
                            @endif">
                            {{ ucfirst($quote->status) }}
                        </span>
                    </div>
                </div>
            </div>
        </header>

        <main class="py-10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                
                <!-- Success/Error Messages -->
                @if(session('success'))
                    <div class="mb-6 rounded-md bg-green-50 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 rounded-md bg-red-50 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Quote Information -->
                <div class="mb-8 overflow-hidden bg-white shadow sm:rounded-lg">
                    <div class="px-4 py-5 sm:px-6">
                        <h2 class="text-lg font-medium leading-6 text-gray-900">Quote Information</h2>
                        <p class="mt-1 max-w-2xl text-sm text-gray-500">Please review the options below and select your preferred choice.</p>
                    </div>
                    <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
                        <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-3">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Quote Number</dt>
                                <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $quote->quote_number }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Number of Options</dt>
                                <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $options->count() }}</dd>
                            </div>
                            @if($quote->expires_at)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Valid Until</dt>
                                <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $quote->expires_at->format('M d, Y') }}</dd>
                            </div>
                            @endif
                        </dl>
                    </div>
                </div>

                <!-- Quote Options -->
                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Available Options</h2>
                    <p class="text-sm text-gray-600 mb-6">Compare the options below and select the one that best fits your needs.</p>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 xl:grid-cols-3">
                    @foreach($options as $option)
                        <div class="relative flex flex-col overflow-hidden rounded-lg border 
                            @if($option->is_selected_by_customer) 
                                border-green-500 bg-green-50 ring-2 ring-green-500
                            @else 
                                border-gray-200 bg-white hover:border-gray-300 hover:shadow-md transition-all
                            @endif">
                            
                            <!-- Selected Badge -->
                            @if($option->is_selected_by_customer)
                                <div class="absolute top-4 right-4">
                                    <span class="inline-flex items-center gap-x-1.5 rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700 ring-1 ring-inset ring-green-600/20">
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                        </svg>
                                        Selected
                                    </span>
                                </div>
                            @endif

                            <div class="flex flex-1 flex-col p-6">
                                <!-- Option Name -->
                                <div class="mb-4">
                                    <h3 class="text-xl font-bold text-gray-900">{{ $option->display_name }}</h3>
                                    @if($option->supplierQuote && $option->supplierQuote->supplier)
                                        <p class="mt-1 text-sm text-gray-500">Supplier: {{ $option->supplierQuote->supplier->name }}</p>
                                    @endif
                                </div>

                                <!-- Price -->
                                <div class="mb-4">
                                    <p class="text-3xl font-bold text-gray-900">
                                        {{ money($option->price_after_commission, $option->currency ?? 'USD') }}
                                    </p>
                                    @if($option->moq)
                                        <p class="mt-1 text-sm text-gray-500">MOQ: {{ $option->moq }}</p>
                                    @endif
                                </div>

                                <!-- Details -->
                                <dl class="space-y-3 text-sm">
                                    @if($option->delivery_time)
                                        <div class="flex justify-between">
                                            <dt class="font-medium text-gray-500">Delivery Time:</dt>
                                            <dd class="text-gray-900">{{ $option->delivery_time }}</dd>
                                        </div>
                                    @endif
                                    
                                    @if($option->highlights)
                                        <div class="pt-3 border-t border-gray-200">
                                            <dt class="font-medium text-gray-700 mb-2">Highlights:</dt>
                                            <dd class="text-gray-600 text-xs leading-relaxed">{{ $option->highlights }}</dd>
                                        </div>
                                    @endif
                                </dl>

                                <!-- Select Button -->
                                <div class="mt-6">
                                    @if($quote->status !== 'accepted')
                                        @if(!$option->is_selected_by_customer)
                                            <form action="{{ route('public.customer-quote.select', $quote->public_token) }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="item_id" value="{{ $option->id }}">
                                                <button type="submit" 
                                                    class="w-full rounded-md bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition-colors">
                                                    Select This Option
                                                </button>
                                            </form>
                                        @else
                                            <button disabled 
                                                class="w-full rounded-md bg-green-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm cursor-not-allowed opacity-75">
                                                ✓ Selected
                                            </button>
                                        @endif
                                    @else
                                        <p class="text-center text-sm text-gray-500">Quote has been accepted</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Footer Note -->
                <div class="mt-10 rounded-lg bg-blue-50 p-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3 flex-1">
                            <h3 class="text-sm font-medium text-blue-800">Need Help?</h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <p>If you have any questions about these options or need additional information, please contact our team.</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-white mt-12">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <p class="text-center text-sm text-gray-500">
                    &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                </p>
            </div>
        </footer>
    </div>
</body>
</html>
