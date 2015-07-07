CREATE TABLE Zones (
  name TEXT PRIMARY KEY,
  player_owner TEXT,
  level_name TEXT,
  minX INTEGER,
  minY INTEGER,
  minZ INTEGER,
  maxX INTEGER,
  maxY INTEGER,
  maxZ INTEGER,
  pvpAvailable BOOLEAN
);

CREATE TABLE Permissions (
  player_name TEXT PRIMARY KEY,
  zone_name TEXT,
  permission_name TEXT

);