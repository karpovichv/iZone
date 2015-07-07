CREATE TABLE IF NOT EXISTS izone_zones (
  name VARCHAR(50) PRIMARY KEY,
  player_owner VARCHAR(50),
  level_name VARCHAR(50),
  minX INT,
  minY INT,
  minZ INT,
  maxX INT,
  maxY INT,
  maxZ INT,
  pvpAvailable BOOLEAN
);

CREATE TABLE IF NOT EXISTS izone_player_permissions (
  player_name VARCHAR(50) PRIMARY KEY,
  zone_name VARCHAR(50),
  permission_name VARCHAR(100)
);