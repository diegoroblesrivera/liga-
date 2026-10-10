<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ----- Vista: tabla de posiciones por torneo -----
        // Uso: SELECT * FROM vista_posiciones WHERE torneo_id = ?
        //      ORDER BY pts DESC, dif DESC, gf DESC;
        DB::statement('DROP VIEW IF EXISTS vista_posiciones');
        DB::statement(<<<'SQL'
CREATE VIEW vista_posiciones AS
SELECT
  r.torneo_id,
  e.id     AS equipo_id,
  e.nombre AS equipo,
  COUNT(*)                                            AS pj,
  SUM(r.gf > r.gc)                                    AS pg,
  SUM(r.gf = r.gc)                                    AS pe,
  SUM(r.gf < r.gc)                                    AS pp,
  SUM(r.gf)                                           AS gf,
  SUM(r.gc)                                           AS gc,
  SUM(r.gf) - SUM(r.gc)                               AS dif,
  SUM(CASE WHEN r.gf > r.gc THEN 3
           WHEN r.gf = r.gc THEN 1 ELSE 0 END)        AS pts
FROM equipos e
JOIN (
  SELECT torneo_id, equipo_local_id  AS equipo_id, goles_local  AS gf, goles_visita AS gc
  FROM partidos WHERE estado = 'finalizado'
  UNION ALL
  SELECT torneo_id, equipo_visita_id AS equipo_id, goles_visita AS gf, goles_local  AS gc
  FROM partidos WHERE estado = 'finalizado'
) r ON r.equipo_id = e.id
GROUP BY r.torneo_id, e.id, e.nombre
SQL);

        // ----- Vista: goleadores por torneo (los autogoles no cuentan) -----
        // Uso: SELECT * FROM vista_goleadores WHERE torneo_id = ? ORDER BY goles DESC;
        DB::statement('DROP VIEW IF EXISTS vista_goleadores');
        DB::statement(<<<'SQL'
CREATE VIEW vista_goleadores AS
SELECT
  p.torneo_id,
  j.id      AS jugador_id,
  j.nombre  AS jugador,
  eq.nombre AS equipo,
  COUNT(*)  AS goles
FROM goles g
JOIN jugadores j ON j.id  = g.jugador_id
JOIN equipos  eq ON eq.id = j.equipo_id
JOIN partidos p  ON p.id  = g.partido_id
WHERE g.tipo <> 'autogol'
GROUP BY p.torneo_id, j.id, j.nombre, eq.nombre
SQL);

        // ----- Trigger: impide superar el cupo de la modalidad -----
        // Blindaje en base de datos; la validación principal va en la app.
        DB::unprepared('DROP TRIGGER IF EXISTS trg_jugadores_cupo');
        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_jugadores_cupo BEFORE INSERT ON jugadores
FOR EACH ROW
BEGIN
  DECLARE cupo     INT DEFAULT NULL;
  DECLARE actuales INT DEFAULT 0;

  SELECT m.max_jugadores INTO cupo
    FROM equipos e
    JOIN modalidades m ON m.id = e.modalidad_id
   WHERE e.id = NEW.equipo_id;

  SELECT COUNT(*) INTO actuales
    FROM jugadores
   WHERE equipo_id = NEW.equipo_id;

  IF cupo IS NOT NULL AND actuales >= cupo THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Cupo de jugadores del equipo completo';
  END IF;
END
SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_jugadores_cupo');
        DB::statement('DROP VIEW IF EXISTS vista_goleadores');
        DB::statement('DROP VIEW IF EXISTS vista_posiciones');
    }
};
