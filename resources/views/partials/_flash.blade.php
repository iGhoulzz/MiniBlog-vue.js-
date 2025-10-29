@if (session('success'))
    <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 4000)" x-show="show"
        class="fixed top-0 left-1/2 transform -translate-x-1/2 bg-green-500 text-white px-48 py-3 rounded-b-lg shadow-lg">
        <p>{{ session('success') }}</p>
    </div>
@endif

@if (session('error'))
    <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 4000)" x-show="show"
        class="fixed top-0 left-1/2 transform -translate-x-1/2 bg-red-500 text-white px-48 py-3 rounded-b-lg shadow-lg">
        <p>{{ session('error') }}</p>
    </div>
@endif

@if ($errors->any())
    <div class="fixed top-0 left-1/2 transform -translate-x-1/2 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-b-lg shadow-lg"
        role="alert">
        <strong class="font-bold">Oops!</strong>
        <span class="block sm:inline">Please correct the errors below.</span>
        <ul class="mt-3 list-disc list-inside text-sm">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
