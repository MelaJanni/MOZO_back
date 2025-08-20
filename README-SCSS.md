# Configuración de SCSS para PDF Viewer

## Estructura de archivos

```
resources/
├── css/
│   └── pdf-viewer.scss    # Estilos organizados del visor PDF
├── views/
│   └── qr/
│       └── table-page.blade.php    # Vista que usa los estilos compilados
└── ...
```

## Instalación y compilación

### 1. Instalar dependencias
```bash
npm install
```

### 2. Compilar estilos para desarrollo (con watch)
```bash
npm run dev
```

### 3. Compilar estilos para producción
```bash
npm run build
```

## Organización del archivo SCSS

El archivo `resources/css/pdf-viewer.scss` está organizado en las siguientes secciones:

1. **Variables CSS Custom Properties**
   - Colores principales del tema
   - Variables de acento y fondos

2. **Mixins SCSS**
   - `glass-effect()`: Efectos de glassmorphism
   - `control-button-base`: Estilos base para botones
   - `fab-animation`: Animaciones del botón flotante

3. **Estilos base**
   - Reset básico y tipografía
   - Layout principal

4. **Componentes principales**
   - Header del visor
   - Footer con controles
   - Área de trabajo del PDF
   - Panel de miniaturas
   - Botón flotante (FAB)
   - Panel del mozo

5. **Media queries**
   - Responsive design para móviles
   - Adaptaciones para tablets

## Beneficios de usar SCSS

- **Organización**: Código más limpio y mantenible
- **Reutilización**: Mixins para evitar duplicación
- **Variables**: Centralización de valores de diseño
- **Anidación**: Estructura jerárquica más clara
- **Compilación**: Optimización automática del CSS final

## Uso en la vista Blade

La vista utiliza la directiva `@vite()` para incluir el archivo compilado:

```blade
@vite('resources/css/pdf-viewer.scss')
```

Esto se compila automáticamente a CSS optimizado cuando se ejecuta `npm run dev` o `npm run build`.