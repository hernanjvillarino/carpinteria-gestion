# Exportación del despiece para corteOPT (formato v1, 2026-11-18)

Resumen de la especificación oficial que pasó Hernan. El exportador de la etapa 4 tiene que cumplirla.

## Archivo

- `.xlsx` con **una sola hoja de datos llamada "Piezas"** (corteOPT lee solo la primera hoja; otras hojas se ignoran).
- Encabezado en la fila 1 con los nombres exactos de abajo, en ese orden. Columnas extra permitidas si su nombre no choca con un alias (usar "Cliente", "Código interno"; no "Detalle", "Nombre", "Tipo de placa").
- Medidas en mm, como celdas **numéricas**.
- Límites por archivo/cálculo: 2000 filas, 8 MB; 20 placas distintas, 500 filas de piezas, 1500 piezas sumando cantidades, cantidad por fila 1 a 500, lado de pieza > 0 y ≤ 6000 mm, placa entera 100 a 6000 mm, espesor 2 a 100 mm. Si se supera, exportar un archivo por mueble o ambiente.

## Columnas (en este orden)

| Columna | Contenido |
|---|---|
| `Descripción` | Texto identificable en el taller, hasta 120 caracteres ("Bajo mesada · Puerta izq.") |
| `Largo (mm)` | Lado en el sentido de la veta / a lo largo de la placa. Obligatorio |
| `Ancho (mm)` | Obligatorio |
| `Cantidad` | Entero 1 a 500 |
| `Tapacanto largos` | 0, 1 o 2: cuántos lados de medida Largo llevan canto |
| `Tapacanto cortos` | 0, 1 o 2: cuántos lados de medida Ancho llevan canto |
| `Puede girar` | `Sí` / `No` (solo importa en placas con veta) |
| `Material` | id corteOPT (tabla de abajo). Obligatorio para que el archivo sea autosuficiente |
| `Espesor (mm)` | 2 a 100, redondeo a 0,1 |
| `Color` | Hasta 60 caracteres, de una **lista cerrada** (para que coincidan los retazos) |
| `Característica` | Hasta 60 caracteres (hidrófugo, 2 caras, texturado) |
| `Marca` | Hasta 40 caracteres; no separa placas |
| `Veta` | Siempre `Sí` o `No` (vacío significa "lo que diga el material") |
| `Largo placa (mm)` | Medida real de la placa entera, si se conoce |
| `Ancho placa (mm)` | Ídem |

Las filas con igual material + espesor + color + característica + veta + largo placa + ancho placa van a la misma placa. Los retazos se reconocen por material + espesor + color + característica + veta, por eso color y característica deben escribirse siempre igual.

No van en la planilla (son configuración del taller en corteOPT): sierra, refilado y retazo mínimo.

## Ids de material

| id | Nombre | Placa por defecto (mm) | Veta por defecto |
|---|---|---|---|
| `melamina` | Melamina sobre aglomerado | 2750 × 1830 | No |
| `melamina_mdf` | Melamina sobre MDF | 2750 × 1830 | No |
| `mdf` | MDF / fibrofácil crudo | 2600 × 1830 | No |
| `mdf_laqueado` | MDF laqueado / pintado | 2600 × 1830 | No |
| `enchapado` | Enchapado natural | 2600 × 1830 | Sí |
| `aglomerado` | Aglomerado crudo | 2600 × 1830 | No |
| `multilaminado` | Multilaminado / terciado | 2440 × 1220 | Sí |
| `fenolico` | Fenólico | 2440 × 1220 | Sí |
| `osb` | OSB | 2440 × 1220 | No |
| `alistonado` | Madera maciza / alistonado | 2440 × 1220 | Sí |
| `hardboard` | Hardboard / chapadur | 2440 × 1830 | No |
| `otro` | Otra placa | 2440 × 1830 | No |

Melaminas "madera" con veta: `melamina` + `Veta = Sí`.

Colores sugeridos para la lista cerrada inicial: Blanco, Negro, Gris humo, Gris grafito, Aluminio, Almendra, Arena, Cajú, Roble Dakar, Roble Mérida, Roble natural, Nogal, Nogal Terracota, Cedro, Paraíso, Teka, Haya, Wengué, Fresno, Crudo (sin revestir).

## Ejemplo

| Descripción | Largo (mm) | Ancho (mm) | Cantidad | Tapacanto largos | Tapacanto cortos | Puede girar | Material | Espesor (mm) | Color | Característica | Marca | Veta | Largo placa (mm) | Ancho placa (mm) |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| Lateral bajo mesada | 720 | 560 | 2 | 1 | 0 | No | melamina | 18 | Blanco | | Faplac | No | 2750 | 1830 |
| Puerta | 716 | 397 | 2 | 2 | 2 | No | melamina | 18 | Roble Dakar | | Egger | Sí | 2800 | 2070 |
| Fondo | 780 | 716 | 1 | 0 | 0 | Sí | hardboard | 3 | Blanco | | | No | | |
| Tapa mesada | 1800 | 600 | 1 | 1 | 0 | No | mdf | 30 | Crudo | hidrófugo | | No | | |
