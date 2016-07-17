# fff_extendFileName

Zur Umbenennung der alten Files folgende Befehle benutzen:

```
rename 's/franken/fff/' *
rename 's/generic/g/' *
sed -i -- 's/franken/fff/g' *.md5
sed -i -- 's/franken/fff/g' *.sha256
sed -i -- 's/generic/g/g' *.sha256
sed -i -- 's/generic/g/g' *.md5
```