Isaac Savegame Parser
=====================

[![Join the chat at https://gitter.im/ihabunek/isaac](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/ihabunek/isaac?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

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


