{{--
  Photos qui se relaient en fondu dans l'arche, avec un léger recul du cadrage (accueil, page d'attente).
  Variables : $images (App\diapos()), $tailles (attribut sizes). 7 s par photo, quel que soit leur nombre :
  le rythme de l'animation est calculé ici (scene.css s'en sert par --diapos-*).
--}}
@php
  $images = array_values($images);
  $nombre = count($images);
  $parPhoto = 7;
  $fondu = 1.7;
  $duree = $parPhoto * $nombre;
  $animation = $nombre > 1 ? 'diapo-' . $nombre : 'none';
  $etapes = fn (float $s) => round($s / $duree * 100, 2) . '%';
  $style = $nombre > 1 ? sprintf(
    '<style>@keyframes %s{0%%{opacity:1;transform:scale(1.1)}%s{opacity:1}%s{opacity:0;transform:scale(1)}%s{opacity:0;transform:scale(1.1)}100%%{opacity:1;transform:scale(1.1)}}</style>',
    $animation, $etapes($parPhoto), $etapes($parPhoto + $fondu), $etapes($duree - $fondu)
  ) : '';
@endphp
<div class="diapos" style="--diapos-animation: {{ $animation }}; --diapos-duree: {{ $duree }}s">
  @foreach ($images as $rang => $image)
    {!! App\media($image, '', [
      'loading' => $rang ? 'lazy' : 'eager',
      'fetchpriority' => $rang ? 'low' : 'high',
      'sizes' => $tailles ?? '(min-width: 980px) 34rem, 80vw',
      // Décalage de la durée d'un fondu : au chargement, la première photo est seule (la dernière a fini de s'effacer).
      'style' => '--diapos-depart: -' . ((($nombre - $rang) % $nombre) * $parPhoto + $fondu) . 's',
    ]) !!}
  @endforeach
</div>
{!! $style !!}
