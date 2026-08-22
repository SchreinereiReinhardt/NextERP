# Betrio 1.9.24

## Mobile: Offene Rapporte korrekt zählen

- Behebt die falsche Anzahl offener Rapporte im Mobile-Dashboard.
- Archivierte Rapporte werden nicht mehr mitgezählt.
- Es werden nur Rapporte aus Projekten gezählt, auf die der angemeldete Benutzer Zugriff hat.
- Die Definition entspricht jetzt der Web-Version:
  - `locked = 0`
  - `archived = 0`
  - nur sichtbare/zugängliche Projekte
