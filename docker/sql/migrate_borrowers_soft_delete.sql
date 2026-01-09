-- Migration: Add soft delete to borrowers table
-- Run this SQL if you already have an existing database

ALTER TABLE borrowers ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL;
