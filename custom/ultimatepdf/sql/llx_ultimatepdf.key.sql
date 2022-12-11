-- ===================================================================
-- Copyright (C) 2012 Regis Houssin  <regis.houssin@capnetworks.com>
-- Copyright (C) 2010-2012 Philippe Grand  <philippe.grand@atoo-net.com>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- ===================================================================


ALTER TABLE llx_ultimatepdf ADD INDEX idx_ultimatepdf_label (fk_user_creat);

ALTER TABLE llx_ultimatepdf ADD CONSTRAINT fk_ultimatepdf_fk_user_creat FOREIGN KEY (fk_user_creat) REFERENCES llx_user (rowid);

