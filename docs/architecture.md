# SPA – Architektúra systému

## 1. Účel dokumentu
Tento dokument popisuje architektúru systému SPA (Samuel Piasecký Academy).
Slúži ako:
- referenčný bod pre vývoj
- zdroj pravdy pre technické rozhodnutia
- kontext pre AI nástroje (Cursor)

Dokument vysvetľuje **prečo je systém navrhnutý tak, ako je**, nie len *čo robí*.

---

## 2. Základné princípy architektúry

### 2.1 DB-first architektúra
- Databáza je primárny zdroj pravdy.
- WordPress slúži ako aplikačná a prezentačná vrstva.
- Business logika nie je viazaná na UI ani post meta.

Dôvody:
- lepšia kontrola nad dátami
- škálovateľnosť
- menšia závislosť od WP interných štruktúr

---

### 2.2 Oddelenie domény od WordPressu
- Doménové entity existujú nezávisle od WP (nie sú to len posts).
- WP users slúžia primárne na autentifikáciu a roly.
- Doménové vzťahy sú riešené v DB tabuľkách.

---

### 2.3 Stabilita pred optimalizáciou
- Uprednostňuje sa čitateľný a predvídateľný kód.
- Žiadne „magické“ riešenia ani hacky.
- Každá optimalizácia musí mať opodstatnenie.

---

## 3. Doménové entity (prehľad)

### 3.1 Tréner
- Samostatná doménová entita.
- Môže (ale nemusí) byť prepojený na WP user.
- Zodpovedá za:
  - vedenie tréningov
  - evidenciu dochádzky
  - prácu s programami

---

### 3.2 Dieťa
- Účastník tréningov.
- Viazané na rodiča (zodpovednú osobu).
- Zapísané do jedného alebo viacerých programov.

---

### 3.3 Rodič
- Zodpovedná osoba za dieťa.
- Môže mať viac detí.
- Slúži ako hlavný kontaktný bod.

---

### 3.4 Program
- Tréningový program definovaný rozvrhovým pravidlom.
- Neobsahuje konkrétne dátumy.
- Slúži ako šablóna pre generovanie tréningových jednotiek.

---

### 3.5 Tréningová jednotka
- Konkrétny dátum a čas tréningu.
- Vzniká generovaním z programu.
- Je základnou jednotkou pre:
  - dochádzku
  - evidenciu účasti

---

### 3.6 Dochádzka
- Manuálna evidencia účasti.
- Vždy viazaná na tréningovú jednotku.
- Nikdy nie priamo na program.

---

## 4. Toky dát (high-level)

### 4.1 Registrácia
1. Rodič zadá údaje.
2. Vytvorí sa rodičovská entita.
3. Vytvorí sa dieťa.
4. Dieťa sa priradí k programu.

---

### 4.2 Generovanie tréningov
1. Program definuje rozvrhové pravidlo.
2. Generátor vytvorí tréningové jednotky.
3. Jednotky sú uložené v DB.
4. UI ich len zobrazuje.

---

### 4.3 Dochádzka
1. Tréner otvorí tréningovú jednotku.
2. Označí účasť detí.
3. Záznam sa uloží ako dochádzka viazaná na jednotku.

---

## 5. Úloha WordPressu v systéme

WordPress:
- poskytuje administračné rozhranie
- spravuje používateľov a roly
- zabezpečuje UI a oprávnenia

WordPress **nie je**:
- zdrojom pravdy dát
- nositeľom business logiky

---

## 6. Rozšíriteľnosť systému
Architektúra umožňuje budúce rozšírenia:
- platby a predplatné
- notifikácie (email / push)
- reporting a štatistiky
- API integrácie

Bez nutnosti zásadného prepisu jadra.

---

## 7. Technické rozhodnutia (zásady)
- Každé zásadné rozhodnutie má byť zaznamenané v `/docs/decisions.md`.
- Zmeny DB modelu musia byť zdokumentované.
- Experimenty sa nerobia priamo v core logike.

---

## 8. Stav lawns of the system
Tento dokument je **živý**.
Aktualizuje sa pri:
- zmene architektúry
- pridaní novej doménovej entity
- zásadnom refaktore
