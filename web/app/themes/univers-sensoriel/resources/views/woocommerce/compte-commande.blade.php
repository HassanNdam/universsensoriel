{{-- Compte client : détail d'une commande. Données préparées dans app/ponts/compte-commande.php. --}}
@php($etapes = App\etapes_commande($commande['retrait']))

<div class="compte compte-commande">
  <p class="compte-retour"><a class="lien-simple" href="{{ esc_url($retour) }}">Toutes mes commandes</a></p>

  <header class="compte-tete">
    <h2>Commande n° {{ $commande['numero'] }}</h2>
    <span class="etat etat-{{ $etat['ton'] }}">{{ $etat['libelle'] }}</span>
  </header>

  <dl class="confirmation-resume compte-resume">
    <div><dt>Date</dt><dd>{{ $commande['date'] }}</dd></div>
    <div><dt>Total</dt><dd>{!! $commande['total'] !!}</dd></div>
    @if ($commande['paiement'])
      <div><dt>Paiement</dt><dd>{{ $commande['paiement'] }}</dd></div>
    @endif
  </dl>

  @if ($etat['etape'] !== null)
    <ol class="confirmation-etapes confirmation-etapes-clair" aria-label="Avancement de la commande">
      @foreach ($etapes as $rang => $etape)
        <li @class(['est-faite' => $rang <= $etat['etape']]) @if ($rang === $etat['etape']) aria-current="step" @endif>
          <span class="confirmation-etape-point" aria-hidden="true">{!! $rang <= $etat['etape'] ? App\icone('coche', 14) : $rang + 1 !!}</span>
          {{ $etape }}
        </li>
      @endforeach
    </ol>
  @endif

  @if ($commande['echec'])
    <p class="compte-vide">
      Paiement refusé : rien n'a été débité.
      <a class="bouton" href="{{ esc_url($commande['url_paiement']) }}">Réessayer le paiement</a>
    </p>
  @endif

  @if ($notes)
    <section class="compte-notes" aria-labelledby="compte-notes">
      <h2 id="compte-notes">Suivi</h2>
      <ol>
        @foreach ($notes as $note)
          <li>
            <time>{{ $note['date'] }}</time>
            <div>{!! wp_kses_post($note['texte']) !!}</div>
          </li>
        @endforeach
      </ol>
    </section>
  @endif

  @include('woocommerce.partials.commande-detail', ['titre' => 'Articles'])

  @if ($apres)
    <div class="confirmation-message">{!! $apres !!}</div>
  @endif
</div>
