Isaac Savegame Parser
=====================

A savegame parser for Binding of Isaac: Rebirth

Database setup
--------------

```sql
CREATE TABLE savegame (
    id serial primary key,
    hash text unique,
    data text,
    uploaded timestamp
);

CREATE UNIQUE INDEX ON savegame (hash);
```


