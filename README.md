<h2>SMSO Prestashop 1.6</h2>

<h3>Functionalitati</h3>
<strong>Modulul ofera urmatoarele functionalitati:</strong>
<br/><br/>
Trimite un SMS catre client in urma urmatoarelor statusuri de comanda:
<ul>
<li>comanda plasata cu succes
<li>comanda livrata
<li>comanda platita
<li>comanda anulata
<li>comanda finalizata
<li>comanda returnata
</ul>
<br/>
Pentru fiecare status de mai sus se poate seta un sablon text care poate sa includa urmatoarele variabile:
<ul>
<li>id comanda
<li>referinta comenzii
<li>numele clientului
<li>totalul comenzii
<li>data comenzii.
</ul>
<br/>
Se pot activa sau dezactiva anumite statusuri, si pe fiecare status se poate seta statusul intern din Prestashop pe care se va face actiunea.

Dupa ce se seteaza SMSO TOKEN si se salveaza setarile se pot vedea numerele de telefon de pe care se poate trimite SMS-urile si se poate selecta unul dintre ele.

Instalare cat si dezinstalarea modulului este rapida din interfata de admin Prestashop.

Interfata de configurare a modului este intuitiva si simpla.

<h3>Instalare</h3>
Modulul se instaleaza cu ajutorul arhivei ZIP prin intermediul adminului in sectiunea Module apoi butonul Adauga un modul nou din partea de sus, se selecteaza apoi arhiva ZIP din PC si apoi se instaleaza.
Dupa instalare modulul se va regasi in lista de module si se va putea configura.<br/>
De asemenea din listarea de module din Prestashop se poate dezactiva, dezinstala sau activa si instala modulul apoi, dupa ce a fost instalat prima data.

<h3>Configurare</h3>
Pentru activarea modulul va trebui sa intrati in contul aplicatiei SMSO https://app.smso.ro/ de unde veti lua TOKEN-ul pe care il adaugati in campul SMSO TOKEN.<br/>
Dupa aceasta va trebui sa salvati, iar daca tokenul este valid vi se vor afisa in campul Sender list, numerele de telefon de pe care puteti sa trimiteti SMS-uri precum si costul fiecaruia.<br/>
Urmatorul pas este sa activati sau sa dezactivati statusurile pentru care se vor trimite SMS-urile, sa modificati textele si sa le asociati cu statusurile interne din Prestashop pe care le folositi.<br/>
La final va fi nevoie sa bifati campul Activeaza pe Da/Yes pentru a incepe trimiterea de SMS-uri.<br/>
La final se va apasa pe butonul Save pentru a salva toate informatiile.
