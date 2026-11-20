{{-- Libellé des boutons « Langue et pays » : langue (mise à jour par le navigateur pour une langue automatique) et devise. --}}
@php
  $langue = App\langue_active();
  $pays = App\pays_actif();
@endphp
<span class="reglages-libelle notranslate" translate="no" data-no-translation>
  @if ($complet ?? false)
    <span data-langue-nom>{{ $langue['nom'] }}</span>@if ($pays) · {{ $pays['nom'] }} ({{ $pays['devise'] }})@endif
  @else
    <span data-langue-code>{{ strtoupper($langue['code']) }}</span>@if ($pays) <span aria-hidden="true">·</span> {{ $pays['devise'] }}@endif
  @endif
</span>
