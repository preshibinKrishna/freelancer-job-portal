@if($submitted)
    <p role="status" class="mt-4 rounded-lg border border-success-100 bg-success-100 p-4 text-sm text-success-700">Your feedback has been submitted for moderation.</p>
@else
    <form class="mt-5 border-t pt-5" wire:submit="submit">
        <h4 class="font-semibold">Share a client review</h4><p class="mt-1 text-sm text-ink-500">Reviews are linked to company-confirmed completed work and checked before publication.</p>
        <div class="mt-4 grid gap-3 sm:grid-cols-[10rem_1fr]"><div><label class="bt-label" for="review-rating">Rating</label><select id="review-rating" class="bt-field" wire:model="rating">@foreach(range(5, 1) as $star)<option value="{{ $star }}">{{ $star }} / 5</option>@endforeach</select>@error('rating')<p class="bt-error">{{ $message }}</p>@enderror</div><div><label class="bt-label" for="review-title">Short title (optional)</label><input id="review-title" class="bt-field" maxlength="160" wire:model="title" placeholder="A thoughtful technical partner"></div></div>
        <div class="mt-3"><label class="bt-label" for="review-text">Your review</label><textarea id="review-text" class="bt-field min-h-24" maxlength="2000" wire:model="reviewText" placeholder="Describe the collaboration and outcomes."></textarea>@error('reviewText')<p class="bt-error">{{ $message }}</p>@enderror</div>
        <div class="mt-3 flex justify-end"><button class="bt-button-secondary" type="submit" wire:loading.attr="disabled" wire:target="submit">Submit review</button></div>
    </form>
@endif
