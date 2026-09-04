# Desarrollo

El sistema es una aplicacion PHP tradicional ejecutada con Apache y MySQL. No utiliza Gradle; por eso `build.gradle.kts` y `settings.gradle.kts` no son necesarios para compilarlo.

## Codigo fuente

El codigo funcional se encuentra en `src/`. El punto de entrada del catalogo es `src/index.php`.

## Dependencias

- PHP 8 o superior con extension `mysqli`.
- MySQL o MariaDB.
- Apache, incluido en XAMPP.
