@props(['status'])
@if($status === \App\Enums\CompanyVerificationStatus::Verified)
    <x-badge tone="success"><span aria-hidden="true">✓</span> Verified company</x-badge>
@elseif($status === \App\Enums\CompanyVerificationStatus::Pending)
    <x-badge tone="warning">Verification in review</x-badge>
@elseif($status === \App\Enums\CompanyVerificationStatus::Rejected)
    <x-badge tone="danger">Verification not approved</x-badge>
@else
    <x-badge tone="neutral">Not verified</x-badge>
@endif
