{{--
  Avis en carrousel horizontal : on fait défiler au doigt, à la molette, au clavier ou avec les flèches.
  Variables : $liste (App\avis()), $lienProduit (bool), $titre (nom de la zone pour les lecteurs d'écran).
--}}
<div class="carrousel" data-carrousel>
  <div class="carrousel-piste" tabindex="0" role="region" aria-label="{{ $titre ?? 'Avis clients' }}" data-carrousel-piste>
    @foreach ($liste as $avis)
      <div class="carrousel-element">
        @include('partials.carte-avis', ['avis' => $avis, 'lienProduit' => $lienProduit ?? true])
      </div>
    @endforeach
  </div>
  <div class="carrousel-commandes" data-carrousel-commandes hidden>
    <div class="carrousel-progression" aria-hidden="true"><span data-carrousel-barre></span></div>
    <button class="carrousel-bouton" type="button" data-carrousel-precedent aria-label="Avis précédents">{!! App\icone('fleche', 18, 'icone-retour') !!}</button>
    <button class="carrousel-bouton" type="button" data-carrousel-suivant aria-label="Avis suivants">{!! App\icone('fleche', 18) !!}</button>
  </div>
</div>
