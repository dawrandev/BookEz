<div class="space-y-2">
    @if($getRecord()->rating)
    <div class="flex items-center space-x-2">
        <div class="text-lg">
            @for($i = 1; $i <= 5; $i++)
                @if($i <=$getRecord()->rating)
                <span class="text-yellow-400">⭐</span>
                @else
                <span class="text-gray-300">☆</span>
                @endif
                @endfor
        </div>
        <span class="text-sm text-gray-600 font-medium">
            {{ $getRecord()->rating }}/5
        </span>
    </div>

    @if($getRecord()->feedback)
    <div class="mt-2 p-3 bg-gray-50 rounded-lg">
        <p class="text-sm text-gray-700 italic">
            "{{ $getRecord()->feedback }}"
        </p>
    </div>
    @endif
    @else
    <div class="text-sm text-gray-500">
        Рейтинг не установлен
    </div>
    @endif
</div>