<x-publico-layout>
    <x-slot name="title">Política de privacidad · {{ config('app.name') }}</x-slot>

    <article class="politica mx-auto max-w-3xl rounded-lg bg-white p-6 shadow-sm sm:p-10">
        <h1 class="titulo-deportivo text-3xl text-balon">Política de privacidad</h1>
        <p class="mt-1 text-sm text-gray-500">Última actualización: {{ $actualizada }}</p>

        <p>
            Esta política explica qué datos personales guarda {{ config('app.name') }} ({{ $dominio }}), para qué, quién los ve
            y cómo puedes pedir que se corrijan o se borren. {{ config('app.name') }} sirve para organizar pichangas
            semanales y campeonatos de fútbol amateur.
        </p>

        <div class="mt-6 rounded-md border-l-4 border-cancha-400 bg-cancha-50 p-4 text-gray-800">
            <strong>Puedes borrar tus datos cuando quieras.</strong>
            Cualquier usuario puede pedir en cualquier momento que borremos sus datos personales, escribiendo a
            <a href="mailto:{{ $correo }}">{{ $correo }}</a>. También puedes eliminar tu cuenta tú mismo desde tu perfil.
        </div>

        <h2>Quién es responsable</h2>
        <p>
            El responsable de tus datos es <strong>Diego Robles Rivera</strong>, en Chile. Para cualquier consulta o
            solicitud sobre tus datos escribe a <a href="mailto:{{ $correo }}">{{ $correo }}</a>.
        </p>
        <p>
            Cada campeonato y cada pichanga tiene un organizador que decide quién participa y administra esa actividad.
            Ese organizador ve los datos de sus participantes que se describen más abajo.
        </p>

        <h2>Qué datos guardamos</h2>
        <p>Solo guardamos lo que tú, el organizador o un árbitro ingresan en la app. No compramos datos ni los obtenemos de otras fuentes.</p>
        <div class="tabla">
            <table>
                <thead><tr><th>Tipo</th><th>Datos</th><th>Cuándo se guardan</th></tr></thead>
                <tbody>
                    <tr><td>Cuenta</td><td>Nombre, correo y contraseña (cifrada, nunca en texto plano)</td><td>Al registrarte</td></tr>
                    <tr><td>Inicio con Google</td><td>Identificador de Google, nombre, correo y foto de perfil</td><td>Si eliges "Continuar con Google"</td></tr>
                    <tr><td>Datos para la portería</td><td>RUN, patente del vehículo y acompañantes (nombre y RUN)</td><td>Solo si los completas en tu perfil</td></tr>
                    <tr><td>Pichangas</td><td>Grupos a los que perteneces, asistencia, equipo, goles y estado de pago</td><td>Al usar una pichanga</td></tr>
                    <tr><td>Invitados</td><td>Nombre y RUN de la persona sin cuenta que llevas (ambos opcionales)</td><td>Si agregas un invitado</td></tr>
                    <tr><td>Comprobantes</td><td>Imagen de tu transferencia, que suele mostrar nombre, banco, número de cuenta y monto</td><td>Al subir un comprobante</td></tr>
                    <tr><td>Campeonatos</td><td>Equipo, nombre, foto, dorsal, posición, goles, tarjetas, convocatorias y pagos del equipo</td><td>Si te inscribes en un campeonato</td></tr>
                    <tr><td>Datos técnicos</td><td>Dirección IP, navegador y registros de errores</td><td>Al visitar el sitio</td></tr>
                </tbody>
            </table>
        </div>
        <p>No pedimos datos sensibles, como salud u origen étnico, salvo lo que aparezca en la imagen de un comprobante que tú decides subir.</p>

        <h2>Para qué los usamos</h2>
        <p>Usamos tus datos solo para que la app funcione. <strong>No los vendemos, no los usamos para publicidad y no armamos perfiles de comportamiento.</strong></p>
        <ul>
            <li>Crear tu cuenta e iniciar sesión.</li>
            <li>Organizar pichangas: cupo, lista de espera, equipos, resultado y división del valor de la cancha.</li>
            <li>Controlar los pagos y sus comprobantes.</li>
            <li>Entregar la lista de ingreso al recinto (RUN, patente, acompañantes e invitados), solo si completaste esos datos.</li>
            <li>Llevar el campeonato: fixture, resultados, tabla, goleadores y suspensiones.</li>
            <li>Enviarte correos del servicio, como recuperar tu contraseña. No enviamos publicidad.</li>
            <li>Mantener el sitio seguro y corregir errores.</li>
        </ul>

        <h2>Quién más ve tus datos</h2>
        <p>Tus datos los ven solo quienes participan contigo en una pichanga o campeonato, según su rol, y los proveedores que hacen funcionar el sitio. No los compartimos con nadie más, salvo que una ley o un tribunal lo exija.</p>
        <div class="tabla">
            <table>
                <thead><tr><th>Quién</th><th>Qué ve</th></tr></thead>
                <tbody>
                    <tr><td>Organizador de tu pichanga</td><td>Tu nombre, asistencia, equipo, goles, estado de pago y tu comprobante. Si confirmas, también tu RUN, patente y acompañantes, para la lista de la portería.</td></tr>
                    <tr><td>Otros jugadores de tu pichanga</td><td>Tu nombre, si vas o no, tu equipo y tus goles. Los confirmados también ven quién no ha pagado, para poder pagar por otros. Nunca ven tu RUN ni tu patente.</td></tr>
                    <tr><td>Quien pagó por ti, o a quien tú le pagaste</td><td>El comprobante usado, que muestra los datos bancarios de quien transfirió.</td></tr>
                    <tr><td>Dueño y administradores de tu campeonato</td><td>Los datos de ese campeonato: equipos, jugadores, partidos, goles, tarjetas y pagos.</td></tr>
                    <tr><td>DT de tu equipo</td><td>Los jugadores de su equipo y sus convocatorias.</td></tr>
                    <tr><td>Árbitro del partido</td><td>Los jugadores de los equipos que se enfrentan, para cargar goles y tarjetas.</td></tr>
                </tbody>
            </table>
        </div>
        <p>Cada campeonato solo ve sus propios datos: el administrador de un campeonato no puede ver los de otro.</p>
        <p>
            <strong>Páginas públicas.</strong> Si el dueño marca su campeonato como público, cualquier persona en internet puede ver
            la tabla de posiciones, las fechas, los resultados y los goleadores, con tu nombre y tu equipo. No se publica tu RUN,
            correo, foto ni ningún dato de pago. Los buscadores, como Google, podrían mostrar esas páginas.
        </p>
        <p><strong>Proveedores.</strong></p>
        <ul>
            <li><strong>SolucionHost</strong> (servidor): guarda la app y la base de datos.</li>
            <li><strong>Google</strong>: solo si eliges iniciar sesión con Google.</li>
            <li><strong>Brevo</strong>: envío de los correos del servicio; recibe tu correo y el contenido del mensaje.</li>
            <li><strong>Bunny Fonts</strong>: tipografías del sitio; recibe tu dirección IP al cargar la página.</li>
            <li><strong>Cloudflare</strong>: gestiona el dominio {{ $dominio }}, reenvía los correos a {{ $correo }} y mide las visitas de forma agregada (páginas vistas, país y tipo de dispositivo), sin cookies y sin identificarte.</li>
        </ul>
        <p>Algunos de estos proveedores tratan datos fuera de Chile. Solo trabajamos con proveedores que ofrecen protecciones adecuadas para esos datos.</p>

        <h2>Cookies</h2>
        <p>Usamos solo las cookies necesarias para que inicies sesión y el sitio sea seguro. No usamos cookies de publicidad ni de analítica: la medición de visitas no usa cookies.</p>
        <ul>
            <li><strong>Sesión</strong>: mantiene tu sesión abierta mientras navegas (2 horas sin actividad).</li>
            <li><strong>XSRF-TOKEN</strong>: protege los formularios contra envíos falsificados (2 horas).</li>
            <li><strong>Recordarme</strong>: te mantiene conectado si marcas "Recordarme" o entras con Google, hasta que cierres sesión.</li>
        </ul>

        <h2>Cuánto tiempo los guardamos</h2>
        <p>
            Guardamos tus datos mientras tengas tu cuenta y los borramos cuando lo pidas o cuando elimines tu cuenta.
            Al eliminar tu cuenta se borran tu cuenta, tus datos de portería, tus acompañantes y tu participación en las pichangas.
            Los resultados históricos de un campeonato (por ejemplo, que hiciste un gol en un partido) pueden conservar tu nombre;
            si quieres que también se quite, pídelo y lo eliminamos.
        </p>

        <h2>Tus derechos</h2>
        <p>
            Puedes pedirnos en cualquier momento <strong>ver, corregir, borrar o llevarte tus datos</strong>, oponerte a un uso
            o pedir que lo suspendamos mientras resolvemos tu solicitud. Te responderemos por escrito dentro de 30 días corridos,
            prorrogables una sola vez por otros 30, como fija la Ley 21.719 sobre protección de datos personales.
        </p>
        <p>
            Para hacerlo, escribe a <a href="mailto:{{ $correo }}">{{ $correo }}</a> con tu nombre, el correo con que te registraste
            y qué necesitas. Varias cosas las puedes hacer tú mismo desde tu perfil: corregir tu nombre y correo, borrar tu RUN,
            patente y acompañantes, o eliminar tu cuenta.
        </p>
        <p>Si no respondemos a tiempo o no estás de acuerdo con la respuesta, puedes reclamar ante la Agencia de Protección de Datos Personales.</p>

        <h2>Seguridad</h2>
        <ul>
            <li>Todo el sitio funciona con conexión cifrada (HTTPS).</li>
            <li>Las contraseñas se guardan cifradas; nadie, ni siquiera el administrador, puede leerlas.</li>
            <li>Los comprobantes de las pichangas se guardan fuera de la parte pública del sitio y solo los abren el jugador, quien pagó por él y el organizador.</li>
            <li>Cada campeonato solo accede a sus propios datos.</li>
            <li>El servidor tiene cortafuegos, bloqueo automático de intentos de acceso repetidos y respaldos periódicos.</li>
        </ul>
        <p>Ningún sistema es 100 % seguro. Si ocurre una filtración que te afecte, te avisaremos a ti y a la autoridad, como exige la ley.</p>

        <h2>Menores de edad</h2>
        <p>
            Personas de cualquier edad pueden participar en {{ config('app.name') }}. Cuando un jugador es menor de edad, el
            <strong>DT de su equipo</strong> es responsable de inscribirlo y de contar con la autorización de su padre, madre o
            tutor. El padre, madre o tutor puede pedir en cualquier momento que se corrijan o se borren los datos del menor,
            escribiendo a <a href="mailto:{{ $correo }}">{{ $correo }}</a>.
        </p>

        <h2>Cambios a esta política</h2>
        <p>
            Si cambiamos qué datos guardamos o para qué los usamos, actualizaremos esta página y su fecha. Si el cambio es
            importante, te avisaremos por correo o al iniciar sesión antes de que se aplique.
        </p>
    </article>
</x-publico-layout>
