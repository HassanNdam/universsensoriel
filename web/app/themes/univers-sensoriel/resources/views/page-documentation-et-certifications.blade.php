{{--
  Documentation et certifications : catalogues, certificats de conformité, fiches techniques.
  Les fichiers se déposent dans Univers Sensoriel › Documentation (app/reglages.php), rangés par rubrique.
  Le texte de la page (éditeur) s'affiche sous l'introduction s'il y en a un.
--}}
@extends('layouts.app')

@section('content')
  @while (have_posts())
    @php
      the_post();
      $rubriques = ['catalogues' => 'Catalogues des produits', 'certificats' => 'Certificats de conformité', 'fiches' => 'Fiches techniques et de sécurité'];
      $parRubrique = [];
      foreach (App\lignes('documents', ['fichier']) as $document) {
        $fichier = (int) ($document['fichier'] ?? 0);
        $chemin = $fichier ? get_attached_file($fichier) : '';
        if (! $chemin || ! is_file($chemin)) {
          continue;
        }
        $parRubrique[$document['rubrique'] ?? 'catalogues'][] = [
          'titre' => ($document['titre'] ?? '') ?: get_the_title($fichier),
          'precision' => $document['description'] ?? '',
          'url' => wp_get_attachment_url($fichier),
          'format' => strtoupper(pathinfo($chemin, PATHINFO_EXTENSION)),
          'taille' => size_format(filesize($chemin), 1),
        ];
      }
    @endphp
    <header class="rayon-tete">
      <div class="conteneur">
        @include('partials.fil')
        <h1>{{ get_the_title() }}</h1>
        @if ($intro = App\texte('documents_intro'))
          <p class="rayon-intro">{{ $intro }}</p>
        @endif
      </div>
    </header>
    <div class="conteneur page-texte">
      <div class="prose">
        @php(the_content())
        @forelse ($rubriques as $cle => $libelle)
          @continue(empty($parRubrique[$cle]))
          <h2>{{ $libelle }}</h2>
          <ul class="documents">
            @foreach ($parRubrique[$cle] as $document)
              <li>
                <a class="document" href="{{ $document['url'] }}" target="_blank" rel="noopener">
                  {!! App\icone('document', 28, 'document-picto') !!}
                  <span class="document-texte">
                    <strong>{{ $document['titre'] }}</strong>
                    <span>{{ implode(' · ', array_filter([$document['precision'], $document['format'], $document['taille']])) }}</span>
                  </span>
                  {!! App\icone('telecharger', 22, 'document-action') !!}
                  <span class="sr-only">Télécharger (nouvel onglet)</span>
                </a>
              </li>
            @endforeach
          </ul>
        @empty
        @endforelse
        @if (! $parRubrique)
          <p>Les documents seront bientôt disponibles ici. Pour en recevoir un dès maintenant : <a href="{{ home_url('/contact/') }}">nous écrire</a>.</p>
        @endif
      </div>
    </div>
  @endwhile
@endsection
