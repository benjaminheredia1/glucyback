# Claves RSA de prueba

Estas dos claves existen **solo para los tests** y estan en el repositorio a
proposito. No son secretos: no protegen nada, no se usan fuera de `tests/`, y
regenerarlas no rompe nada mas que los tests que las leen.

- `auth0-firma-de-prueba.pem` — hace de clave del tenant de Auth0. Los tests
  firman tokens con ella y publican su parte publica como JWKS.
- `auth0-clave-intrusa-de-prueba.pem` — clave ajena, para comprobar que un
  token firmado con otra cosa se rechaza.

## Por que fijas y no generadas al vuelo

`openssl_pkey_new()` necesita encontrar `openssl.cnf`, y en varias
instalaciones de PHP en Windows (Laragon entre ellas) no esta configurado:
devuelve `false` con `configuration file routines::no such file`. Leer una
clave ya existente y firmar con ella no depende de ese archivo, asi que fijar
las claves hace los tests portables sin renunciar a nada: las firmas siguen
siendo RS256 de verdad y la verificacion contra las JWKS es la real.

## Regenerarlas

```bash
openssl genpkey -algorithm RSA -pkeyopt rsa_keygen_bits:2048 -out auth0-firma-de-prueba.pem
openssl genpkey -algorithm RSA -pkeyopt rsa_keygen_bits:2048 -out auth0-clave-intrusa-de-prueba.pem
```
