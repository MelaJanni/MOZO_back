# Organización de CSS para PDF Viewer

## Estructura de archivos

```
public/
├── css/
│   └── pdf-viewer.css    # Estilos organizados del visor PDF (CSS compilado)
├── images/
│   └── logo.jpeg         # Logo de Mozo
└── ...

resources/
├── css/
│   └── pdf-viewer.scss   # Archivo SCSS fuente (para referencia)
├── views/
│   └── qr/
│       └── table-page.blade.php    # Vista que usa los estilos
└── ...
```

## Solución implementada

Se creó un archivo CSS estático optimizado en `public/css/pdf-viewer.css` que contiene todos los estilos del visor PDF organizados de manera clara y mantenible.

## Organización del archivo CSS

El archivo está organizado en las siguientes secciones:

1. **Variables CSS Custom Properties**
   - Colores principales del tema
   - Variables de acento y fondos

2. **Estilos base**
   - Reset básico y tipografía
   - Layout principal

3. **Componentes principales**
   - Header del visor
   - Footer con controles glassmorphism
   - Área de trabajo del PDF
   - Panel de miniaturas
   - Botón flotante (FAB) con logo
   - Panel del mozo

4. **Animaciones**
   - Spin para loading
   - Pulse para botones
   - Transiciones suaves

5. **Media queries**
   - Responsive design para móviles
   - Adaptaciones para tablets

## Uso en la vista Blade

La vista utiliza la función `asset()` de Laravel para incluir el archivo CSS:

```blade
<link rel="stylesheet" href="{{ asset('css/pdf-viewer.css') }}">
```

## Beneficios de esta organización

- **Sin dependencias**: No requiere compilación
- **Compatible con producción**: Funciona directamente en servidor
- **Mantenible**: Código organizado y comentado
- **Performance**: CSS optimizado y minificado
- **Portable**: Archivo único autocontenido

## Características del diseño

- **Glassmorphism**: Efectos de transparencia moderna
- **Controles en footer**: Diseño estético y funcional
- **Responsive**: Adaptación automática a dispositivos
- **Bootstrap compatible**: Usa clases utilitarias de Bootstrap
- **Font Awesome**: Iconos profesionales