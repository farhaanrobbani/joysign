@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white dark:placeholder-gray-400 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm']) }}>
