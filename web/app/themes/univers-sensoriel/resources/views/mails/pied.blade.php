{{-- Pied de tous les e-mails : referme le corps et la carte ouverts par mails/entete. --}}
            </td>
          </tr>
          <tr>
            <td class="pied" align="center">
              @if ($contacts)
                <p class="pied-contacts">
                  @foreach ($contacts as [$libelle, $url])
                    <a href="{{ $url }}">{{ $libelle }}</a>@if (! $loop->last)<span class="point"> &nbsp;·&nbsp; </span>@endif
                  @endforeach
                </p>
              @endif
              <p class="pied-liens">
                @foreach ($liens as [$libelle, $url])
                  <a href="{{ $url }}">{{ $libelle }}</a>@if (! $loop->last)<span class="point"> &nbsp;·&nbsp; </span>@endif
                @endforeach
              </p>
              <p class="pied-mention">{{ $marque }}@if ($boutiques) &nbsp;·&nbsp; {{ $boutiques }}@endif</p>
              <p class="pied-signature">Site réalisé par <a href="https://hassan-ndam.com">hassan-ndam.com</a></p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
