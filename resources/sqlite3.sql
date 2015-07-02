CREATE TABLE Zones (
  name TEXT PRIMARY KEY,
  player_owner TEXT,
  level_name TEXT,
  minX INTEGER,
  minY INTEGER,
  minZ INTEGER,
  maxX INTEGER,
  maxY INTEGER,
  maxZ INTEGER
);

CREATE TABLE Permissions (
  player_name TEXT PRIMARY KEY,
  permission_name TEXT

);