# SPA – Procesy a workflow

## 1. Účel dokumentu
Tento dokument popisuje hlavné procesy (workflows) systému SPA.
Zameriava sa na:
- tok dát
- zodpovednosti jednotlivých rolí
- väzby medzi entitami

Dokument nepopisuje UI ani technickú implementáciu,
ale **správanie systému ako celku**.

---

## 2. Role v systéme

### 2.1 Admin
- spravuje systém
- má prístup ku všetkým dátam
- rieši konfigurácie a opravy

---

### 2.2 Tréner
- pracuje s programami
- vedie tréningové jednotky
- zapisuje dochádzku

---

### 2.3 Rodič
- registruje dieťa
- sleduje účasť
- komunikuje s akadémiou

---

## 3. Workflow: Registrácia dieťaťa

### Cieľ
- vytvoriť rodiča
- vytvoriť dieťa
- priradiť dieťa k programu

### Kroky
1. Rodič vyplní registračný formulár.
2. Systém overí základné údaje.
3. Vytvorí sa záznam rodiča (ak neexistuje).
4. Vytvorí sa záznam dieťaťa.
5. Dieťa sa priradí k vybranému programu.
6. Stav registrácie sa nastaví ako „aktívna“.

### Výsledok
- dieťa existuje v systéme
- je priradené k programu
- rodič je kontaktná osoba

---

## 4. Workflow: Správa programov

### Cieľ
- definovať tréningový program
- pripraviť podklady pre generovanie tréningov

### Kroky
1. Tréner alebo admin vytvorí program.
2. Definuje:
   - rozvrhové pravidlo
   - čas tréningu
   - periodicitu
3. Program sa uloží do DB.
4. Program je dostupný pre priraďovanie detí.

### Poznámka
Program **neobsahuje konkrétne dátumy**.

---

## 5. Workflow: Generovanie tréningových jednotiek

### Cieľ
- vytvoriť konkrétne tréningy z programu

### Kroky
1. Systém načíta rozvrhové pravidlo programu.
2. Vypočíta dátumy tréningov.
3. Pre každý dátum vytvorí tréningovú jednotku.
4. Jednotky uloží do DB.

### Výsledok
- existujú konkrétne tréningové jednotky
- sú viazané na program a trénera

---

## 6. Workflow: Dochádzka

### Cieľ
- zaznamenať účasť detí na tréningu

### Kroky
1. Tréner otvorí tréningovú jednotku.
2. Zobrazí sa zoznam detí priradených k programu.
3. Tréner označí účasť každého dieťaťa.
4. Záznamy sa uložia ako dochádzka.

### Dôležité
- Dochádzka je vždy viazaná na tréningovú jednotku.
- Nikdy nie priamo na program.

---

## 7. Workflow: Úpravy a výnimky

### Zmena programu
- zmena rozvrhu:
  - neovplyvní už vytvorené tréningové jednotky
  - aplikuje sa len na nové generovanie

### Zrušenie tréningu
- tréningová jednotka sa označí ako zrušená
- historické dáta sa nemažú

---

## 8. Chybové a okrajové prípady

### Dieťa bez programu
- dieťa nemôže mať dochádzku
- je evidované, ale neaktívne

### Tréning bez trénera
- systém ho označí ako nekompletný
- vyžaduje zásah admina

---

## 9. Súvisiace dokumenty
- `architecture.md`
- `db-schema.md`
- `decisions.md`
