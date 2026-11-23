# Agenda personal de contactos (versión 2)

Aplicación web hecha con PHP y MySQL donde cada persona crea su cuenta y guarda sus propios contactos. Nadie puede ver ni tocar los contactos de otro usuario, ni siquiera cambiando el `id` en la URL.

En esta segunda versión se añadieron **búsqueda, ordenamiento, categorías y favoritos**, y la aplicación quedó publicada en un hosting gratuito.

## 🌐 URL pública

**https://agendapersonal.site.je**

## 🖼️ Capturas de pantalla

| Inicio de sesión | Panel con filtros |
|---|---|
| ![Login](docs/capturas/login.png) | ![Panel](docs/capturas/panel.png) |

| Búsqueda | Favoritos |
|---|---|
| ![Búsqueda](docs/capturas/busqueda.png) | ![Favoritos](docs/capturas/favoritos.png) |

| Nuevo contacto con categoría |
|---|
| ![Formulario](docs/capturas/formulario.png) |

## ✨ ¿Qué se puede hacer?

- Crear una cuenta (el correo no se puede repetir), iniciar y cerrar sesión.
- Ver un panel privado con tu nombre, tu correo y tus contactos.
- Agregar, editar y eliminar contactos (al eliminar pide confirmación).
- **Buscar** contactos por nombre, correo o ciudad.
- **Ordenar** por nombre, ciudad o fecha de creación, de forma ascendente o descendente.
- **Clasificar** cada contacto en una categoría: Familia, Amigos, Trabajo, Universidad u Otro, y filtrar por ella.
- **Marcar favoritos** con la estrella ★ y ver solo los favoritos.

## 🆕 Mejoras respecto a la versión anterior

| Mejora | Qué cambió |
|---|---|
| **A. Búsqueda** | Cuadro de búsqueda con selector del campo (todos, nombre, correo o ciudad). Los caracteres `%` y `_` se buscan como texto normal. |
| **B. Ordenamiento** | Selector de criterio (nombre, ciudad, fecha de creación) y de sentido. Los valores se validan contra una lista permitida. |
| **C. Categorías** | Nueva tabla `categorias` (5 registros) y campo `id_categoria` en `contactos`, relacionado con clave foránea. Filtro por categoría en el panel. |
| **D. Favoritos** | Nuevo campo `favorito` (Sí/No), botón de estrella en cada fila y filtro "Solo favoritos". |
| Extra | Nuevo campo `fecha_creacion` en `contactos`, que se muestra en el listado y sirve para ordenar. |
| Extra | Las credenciales de la base de datos salieron del código: se leen de `config/local.php`, que no se sube al repositorio. |

Todas las búsquedas y filtros se combinan entre sí y siguen limitados a los contactos del usuario con sesión iniciada.

## 🛠️ Tecnologías utilizadas

- PHP 8 o superior
- MySQL / MariaDB
- HTML y CSS
- InfinityFree (hosting gratuito) y GitHub

## 💻 Instalar el proyecto localmente

Necesitas un servidor local con Apache, PHP y MySQL; lo más fácil es [XAMPP](https://www.apachefriends.org).

1. Descarga o clona el proyecto dentro de `htdocs` (`C:\xampp\htdocs\agenda-contactos`):

   ```bash
   git clone https://github.com/<tu-usuario>/agenda-contactos.git
   ```

2. Enciende **Apache** y **MySQL** desde el panel de XAMPP.
3. Entra a `http://localhost/phpmyadmin`, ve a **Importar** y carga `database.sql`.
4. Si tu MySQL tiene otra contraseña, copia `config/local.example.php` como `config/local.php` y ajusta los datos.
5. Abre `http://localhost/agenda-contactos/`, crea una cuenta y listo.

## 🚀 Despliegue en hosting

La guía completa está en [`DESPLIEGUE.md`](DESPLIEGUE.md). En resumen: se crea la base de datos desde el panel del hosting, se importa `database_hosting.sql`, se sube el proyecto a `htdocs` y se crea `config/local.php` con los datos de conexión del hosting.

## 🔒 Seguridad

- Contraseñas con `password_hash()` y `password_verify()`.
- Todas las páginas privadas verifican que haya una sesión activa.
- Cada consulta filtra por el usuario de la sesión, así que cada quien solo accede a lo suyo.
- Formularios validados en el servidor, consultas preparadas, protección contra XSS y CSRF.
- Mensajes de error genéricos que no revelan datos sensibles.

## 📁 Estructura

```
agenda-contactos/
├── assets/style.css           Estilos
├── config/
│   ├── database.php           Conexión a la base de datos
│   └── local.example.php      Plantilla de credenciales (copiar a local.php)
├── includes/                  Sesiones, validaciones y piezas de la interfaz
├── docs/capturas/             Capturas de pantalla
├── registro.php               Crear cuenta
├── login.php / logout.php     Entrar y salir
├── panel.php                  Panel: búsqueda, orden, categorías y favoritos
├── contacto_nuevo.php         Agregar contacto
├── contacto_editar.php        Editar contacto
├── contacto_eliminar.php      Confirmar y eliminar contacto
├── contacto_favorito.php      Marcar / quitar favorito
├── database.sql               Base de datos completa (instalación local)
├── database_hosting.sql       Igual, sin CREATE DATABASE (para el hosting)
└── DESPLIEGUE.md              Guía de publicación en el hosting
```
