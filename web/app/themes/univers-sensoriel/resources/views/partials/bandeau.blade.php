{{--
  Bandeau du haut (app/bandeau.php) : l'événement du moment, puis les messages permanents qui défilent.
  Les copies qui servent à la boucle du défilement sont cachées aux lecteurs d'écran : ils lisent la liste une fois.
  $sans_lien (page d'attente) : l'événement s'affiche sans son lien, qui mènerait vers une boutique encore fermée.
--}}
@php
  $messages = App\bandeau_messages();
  $evenement = App\evenement();
  if ($evenement && ($sans_lien ?? false)) {
    $evenement['lien'] = '';
  }
@endphp

@if ($evenement)
  @php
    [$gauche, $droite] = App\illustrations($evenement['theme']);
    $compte = App\compte_a_rebours($evenement['reste']);
    $balise = $evenement['lien'] ? 'a' : 'div';
  @endphp
  <{{ $balise }} class="evenement evenement--{{ $evenement['theme'] }}" @if ($evenement['lien']) href="{{ $evenement['lien'] }}" @endif data-evenement>
    <span class="evenement-particules" aria-hidden="true">{!! App\particules() !!}</span>
    <svg class="evenement-ill evenement-ill-gauche" viewBox="0 0 170 56" aria-hidden="true" focusable="false">{!! $gauche !!}</svg>
    <svg class="evenement-ill evenement-ill-droite" viewBox="0 0 170 56" aria-hidden="true" focusable="false">{!! $droite !!}</svg>

    <span class="evenement-contenu">
      <strong class="evenement-titre">{{ $evenement['titre'] }}</strong>
      @if ($evenement['message'])
        <span class="evenement-message">{{ $evenement['message'] }}</span>
      @endif
      @if ($evenement['echeance'])
        <span class="evenement-compte" data-compte="{{ $evenement['echeance'] }}">
          <span class="evenement-compte-libelle">Plus que</span>
          <span class="evenement-chiffres" aria-hidden="true">
            <span><b data-j>{{ $compte['j'] }}</b>j</span>
            <span><b data-h>{{ $compte['h'] }}</b>h</span>
            <span><b data-m>{{ $compte['m'] }}</b>m</span>
            <span class="evenement-secondes"><b data-s>{{ $compte['s'] }}</b>s</span>
          </span>
          <span class="sr-only">{{ $compte['j'] }} jours et {{ (int) $compte['h'] }} heures</span>
        </span>
      @endif
      @if ($evenement['lien'] && $evenement['lien_texte'])
        <span class="evenement-lien">{{ $evenement['lien_texte'] }} {!! App\icone('fleche', 14) !!}</span>
      @endif
    </span>
  </{{ $balise }}>
@endif

@if ($messages)
  <div class="bandeau" role="note">
    <p class="sr-only">{{ implode(' · ', $messages) }}</p>
    <div class="bandeau-piste" aria-hidden="true">
      @foreach ([1, 2] as $moitie)
        <ul class="bandeau-defile">
          {{-- Chaque moitié répète les messages pour couvrir les écrans les plus larges. --}}
          @foreach (range(1, max(2, intdiv(8, count($messages)))) as $tour)
            @foreach ($messages as $message)
              <li @if ($tour > 1) class="bandeau-copie" @endif>{!! App\icone('etoile', 11) !!}<span>{{ $message }}</span></li>
            @endforeach
          @endforeach
        </ul>
      @endforeach
    </div>
  </div>
@endif
