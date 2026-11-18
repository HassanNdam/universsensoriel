@php($fil = App\fil_ariane())
@if (count($fil) > 1)
  <nav class="fil" aria-label="Fil d'Ariane">
    <ol>
      @foreach ($fil as $rang => [$titre, $url])
        <li>
          @if ($url)
            <a href="{{ $url }}"><span>{{ $titre }}</span></a>
          @else
            <span aria-current="page">{{ $titre }}</span>
          @endif
        </li>
      @endforeach
    </ol>
  </nav>
@endif
