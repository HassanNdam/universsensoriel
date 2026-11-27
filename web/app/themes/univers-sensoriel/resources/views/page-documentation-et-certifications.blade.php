{{--
  Documentation et certifications : catalogues, certificats de conformité, engagements du fabricant.
  Documents, rubriques et textes : Univers Sensoriel › Documentation (app/reglages.php, app/documents.php).
  Une rubrique dont un document a une couverture s'affiche en vignettes, les autres en lignes.
  Le texte de la page (éditeur) s'affiche avant les rubriques s'il y en a un.
--}}
@extends('layouts.app')

@section('content')
  @while (have_posts())
    @php
      the_post();
      $rubriques = App\documents();
      $bouton = App\texte('documents_bouton');
    @endphp
    <header class="rayon-tete">
      <div class="conteneur">
        @include('partials.fil')
        <h1>{{ get_the_title() }}</h1>
        @if ($intro = App\texte('documents_intro'))
          <p class="rayon-intro">{{ $intro }}</p>
        @endif
        @if (count($rubriques) > 1)
          <ul class="pastilles pastilles-centre">
            @foreach (array_keys($rubriques) as $cle)
              <li><a class="pastille" href="#rubrique-{{ $cle }}">{{ App\titre_rubrique($cle) }}</a></li>
            @endforeach
          </ul>
        @endif
      </div>
    </header>

    <div class="conteneur documentation">
      @if (trim(get_the_content()) !== '')
        <div class="prose documentation-texte">@php(the_content())</div>
      @endif

      @forelse ($rubriques as $cle => $documents)
        <section class="documentation-rubrique" id="rubrique-{{ $cle }}" aria-labelledby="titre-{{ $cle }}">
          <header class="documentation-tete">
            <h2 id="titre-{{ $cle }}">{{ App\titre_rubrique($cle) }}</h2>
            @if ($cle === 'certificats' && ($garantie = App\texte('documents_garantie')))
              <p>{{ $garantie }}</p>
            @endif
          </header>

          @if (array_filter(array_column($documents, 'couverture')))
            <ul class="catalogues">
              @foreach ($documents as $document)
                <li>
                  <a class="catalogue" href="{{ $document['url'] }}" target="_blank" rel="noopener">
                    <span class="catalogue-couverture">
                      @if ($image = App\media($document['couverture'], '', ['sizes' => '(min-width: 1100px) 330px, (min-width: 700px) 30vw, 45vw']))
                        {!! $image !!}
                      @else
                        {!! App\icone('document', 44) !!}
                      @endif
                      <span class="catalogue-format">{{ $document['format'] }}</span>
                    </span>
                    <strong class="catalogue-titre">{{ $document['titre'] }}</strong>
                    <span class="catalogue-precision">{{ implode(' · ', array_filter([$document['precision'], $document['taille']])) }}</span>
                    @if ($bouton)
                      <span class="catalogue-action">{{ $bouton }} {!! App\icone('fleche', 16) !!}</span>
                    @endif
                    <span class="sr-only">(nouvel onglet)</span>
                  </a>
                </li>
              @endforeach
            </ul>
          @else
            <ul class="documents">
              @foreach ($documents as $document)
                <li>
                  <a class="document" href="{{ $document['url'] }}" target="_blank" rel="noopener">
                    {!! App\icone('document', 28, 'document-picto') !!}
                    <span class="document-texte">
                      <strong>{{ $document['titre'] }}</strong>
                      <span>{{ implode(' · ', array_filter([$document['precision'], $document['format'] . ' ' . $document['taille']])) }}</span>
                    </span>
                    {!! App\icone('telecharger', 22, 'document-action') !!}
                    <span class="sr-only">{{ $bouton }} (nouvel onglet)</span>
                  </a>
                </li>
              @endforeach
            </ul>
          @endif
        </section>
      @empty
        <p class="documentation-vide">
          {{ App\texte('documents_vide') }}
          <a class="lien-or" href="{{ home_url('/contact/') }}">Contact {!! App\icone('fleche', 16) !!}</a>
        </p>
      @endforelse
    </div>
  @endwhile
@endsection
