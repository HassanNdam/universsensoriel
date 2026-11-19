{{-- Poussière d'or : quelques grains qui montent lentement et s'éteignent (accueil, page d'attente, scene.css).
     Position, taille, durée et départ de chaque grain sont fixes, pour un rendu identique à chaque visite. --}}
@php($grains = [[8, 3, 17, 0], [17, 2, 21, 6], [29, 4, 19, 11], [41, 2, 23, 3], [52, 3, 18, 14], [61, 2, 22, 8], [70, 4, 20, 1], [79, 2, 24, 12], [87, 3, 19, 5], [94, 2, 21, 16]])
<div class="poussiere" aria-hidden="true">
  @foreach ($grains as [$x, $taille, $duree, $depart])
    <span style="--x: {{ $x }}%; --taille: {{ $taille }}px; --duree: {{ $duree }}s; --depart: -{{ $depart }}s"></span>
  @endforeach
</div>
