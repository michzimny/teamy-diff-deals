# teamy-diff-deals

Program umożliwia wyświetlenie w protokole JFR Teamy na stronie wielu rozkładów rozdań, tj. innego rozkładu dla każdego stołu.

## Instalacja

W katalogu z plikami html na serwerze należy umieścić .htaccess oraz pliki PHP.

## Dodanie rozkładów

W Adminie nie podpina się do turnieju żadnych rozkładów.

Pliki PBN z rozkładami należy wrzucić na serwerze pod ściśle określonymi nazwami. 
Dostęp do plików PBN przez HTTP jest zablokowany dyrektywą w .htaccess, ale należy to przetestować w przypadku korzystania z serwera z niestandardową konfiguracją.

Nazwy plików na FTP muszą być następującej postaci:
```
{PREFIKS}-r{RUNDA}-t{STÓŁ}-b{OD_ROZDANIA}.pbn
```

gdzie:

* {PREFIKS} - prefiks plików turnieju generowanych przez Admina,
* {RUNDA} - numer rundy w Adminie, w play-offach to zawsze będzie 1,
* {STÓŁ} - numer stołu, dla którego przeznaczone są rozkłady,
* {OD_ROZDANIA} - wskazanie rozdania w meczu, dla którego przeznaczone jest pierwsze rozdanie z tego pliku pbn.

Na przykład turniej o prefiksie "nepo1" z 5 różnymi zestawami rozdań dla kolejnych stołów wymaga dodania następujących plików z rozkładami:

```
nepo1-r1-t1-b1.pbn
nepo1-r1-t2-b1.pbn
nepo1-r1-t3-b1.pbn
nepo1-r1-t4-b1.pbn
nepo1-r1-t5-b1.pbn
```
