{{-- Compte client : liste des commandes. Données préparées par App\liste_commandes() (app/compte.php). --}}
<div class="compte compte-commandes">
  {!! $avant !!}

  @if ($commandes)
    <ul class="commandes">
      @foreach ($commandes as $commande)
        <li class="commande-carte">
          <div class="commande-carte-tete">
            <p class="commande-carte-numero">
              <a href="{{ esc_url($commande['url']) }}">Commande n° {{ $commande['numero'] }}</a>
              <small>{{ $commande['date'] }}</small>
            </p>
            <span class="etat etat-{{ $commande['etat']['ton'] }}">{{ $commande['etat']['libelle'] }}</span>
          </div>

          <div class="commande-carte-corps">
            <span class="commande-carte-vignettes" aria-hidden="true">
              @foreach ($commande['vignettes'] as $vignette)
                <span class="confirmation-vignette">{!! $vignette !!}</span>
              @endforeach
              @if ($commande['reste'])
                <span class="confirmation-vignette commande-carte-reste">+{{ $commande['reste'] }}</span>
              @endif
            </span>
            <p class="commande-carte-total">
              <small>{{ $commande['articles'] }}</small>
              {!! $commande['total'] !!}
            </p>
          </div>

          <p class="commande-carte-actions">
            @foreach ($commande['actions'] as $action)
              @if ($action['cle'] === 'view')
                <a class="lien-or" href="{{ esc_url($action['url']) }}" aria-label="Voir la commande n° {{ $commande['numero'] }}">Voir le détail {!! App\icone('fleche', 16) !!}</a>
              @else
                <a class="lien-simple" href="{{ esc_url($action['url']) }}">{{ $action['nom'] }}</a>
              @endif
            @endforeach
          </p>
        </li>
      @endforeach
    </ul>

    {!! $pagination !!}

    @if ($precedente || $suivante)
      <nav class="compte-pages" aria-label="Pages des commandes">
        @if ($precedente)
          <a class="lien-simple" href="{{ esc_url($precedente) }}">Plus récentes</a>
        @endif
        @if ($suivante)
          <a class="lien-simple" href="{{ esc_url($suivante) }}">Plus anciennes</a>
        @endif
      </nav>
    @endif
  @else
    <div class="compte-vide">
      <p>Aucune commande pour l'instant.</p>
      <a class="bouton" href="{{ esc_url(wc_get_page_permalink('shop')) }}">Voir la boutique</a>
    </div>
  @endif

  {!! $apres !!}
</div>
