-- ============================================================================
-- Copyright (C) 2014-2022   Philippe Grand		<philippe.grand@atoo-net.com>
-- Copyright (C) 2014-2017   Regis Houssin		<regis.houssin@capnetworks.com>
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see <http://www.gnu.org/licenses/>.

INSERT INTO llx_c_ultimatepdf_line
    (rowid,code,label,description,active)
VALUES
    (1, 'TEXTE1', 'texte de garantie', 'Garantie 2 ans pièces et main d''œuvre, retour en atelier (Hors filtre et pièce d''usure)', 1);

INSERT INTO llx_c_ultimatepdf_title
    (rowid,code,label,description,active)
VALUES
    (1, 'TITLE1', 'Facture Proforma', 'Facture proforma', 1);

INSERT INTO llx_c_type_contact
    (rowid, element, source, code, libelle, active )
VALUES
    (42, 'propal', 'external', 'SHIPPING', 'Contact client livraison', 1);

INSERT INTO llx_extrafields
    (name, entity, elementtype, list, label, type, size, pos, param)
VALUES
    ('newline', 1, 'propal', '1', 'DictionaryUltimatepdfLine', 'sellist', '', 0, 'a:1:{s:7:"options";a:1:{s:29:"c_ultimatepdf_line:label:code";N;}}')
;
INSERT INTO llx_extrafields
    (name, entity, elementtype, list, label, type, size, pos, param)
VALUES
    ('newtitle', 1, 'propal', '1', 'DictionaryUltimatepdfTitle', 'sellist', '', 0, 'a:1:{s:7:"options";a:1:{s:30:"c_ultimatepdf_title:label:code";N;}}')
;
INSERT INTO llx_extrafields
    (name, entity, elementtype, list, label, type, size, pos, param)
VALUES
    ('signature', 1, 'propal', '1', 'Signature du responsable', 'sellist', '', 0, 'a:1:{s:7:"options";a:1:{s:16:"user:login:rowid";N;}}')
;
INSERT INTO llx_extrafields
    (name, entity, elementtype, list, label, type, size, pos, param)
VALUES
    ('newline', 1, 'commande', '1', 'DictionaryUltimatepdfLine', 'sellist', '', 0, 'a:1:{s:7:"options";a:1:{s:29:"c_ultimatepdf_line:label:code";N;}}')
;
INSERT INTO llx_extrafields
    (name, entity, elementtype, list, label, type, size, pos, param)
VALUES
    ('newtitle', 1, 'commande', '1', 'DictionaryUltimatepdfTitle', 'sellist', '', 0, 'a:1:{s:7:"options";a:1:{s:30:"c_ultimatepdf_title:label:code";N;}}')
;
INSERT INTO llx_extrafields
    (name, entity, elementtype, list, label, type, size, pos, param)
VALUES
    ('signature', 1, 'commande', '1', 'Signature du responsable', 'sellist', '', 0, 'a:1:{s:7:"options";a:1:{s:16:"user:login:rowid";N;}}')
;
INSERT INTO llx_extrafields
    (name, entity, elementtype, list, label, type, size, pos, param)
VALUES
    ('newline', 1, 'facture', '1', 'DictionaryUltimatepdfLine', 'sellist', '', 0, 'a:1:{s:7:"options";a:1:{s:29:"c_ultimatepdf_line:label:code";N;}}');
INSERT INTO llx_extrafields
    (name, entity, elementtype, list, label, type, size, pos, param)
VALUES
    ('newtitle', 1, 'facture', '1', 'DictionaryUltimatepdfTitle', 'sellist', '', 0, 'a:1:{s:7:"options";a:1:{s:30:"c_ultimatepdf_title:label:code";N;}}')
;
INSERT INTO llx_extrafields
    (name, entity, elementtype, list, label, type, size, pos, param)
VALUES
    ('signature', 1, 'facture', '1', 'Signature du responsable', 'sellist', '', 0, 'a:1:{s:7:"options";a:1:{s:16:"user:login:rowid";N;}}')
;
INSERT INTO llx_extrafields
    (name, entity, elementtype, list, label, type, size, pos, param)
VALUES
    ('signature', 1, 'commande_fournisseur', '1', 'Signature du responsable', 'sellist', '', 0, 'a:1:{s:7:"options";a:1:{s:16:"user:login:rowid";N;}}')
;
INSERT INTO llx_extrafields
    (name, entity, elementtype, list, label, type, size, pos, param)
VALUES
    ('newline', 1, 'expedition', '1', 'DictionaryUltimatepdfLine', 'sellist', '', 0, 'a:1:{s:7:"options";a:1:{s:29:"c_ultimatepdf_line:label:code";N;}}');
INSERT INTO llx_extrafields
    (name, entity, elementtype, list, label, type, size, pos, param)
VALUES
    ('newtitle', 1, 'expedition', '1', 'DictionaryUltimatepdfTitle', 'sellist', '', 0, 'a:1:{s:7:"options";a:1:{s:30:"c_ultimatepdf_title:label:code";N;}}')
;
INSERT INTO llx_extrafields
    (name, entity, elementtype, list, label, type, size, pos, param)
VALUES
    ('signature', 1, 'expedition', '1', 'Signature du responsable', 'sellist', '', 0, 'a:1:{s:7:"options";a:1:{s:16:"user:login:rowid";N;}}')
;
INSERT INTO llx_extrafields
    (name, entity, elementtype, list, label, type, size, pos, param)
VALUES
    ('newline', 1, 'fichinter', '1', 'DictionaryUltimatepdfLine', 'sellist', '', 0, 'a:1:{s:7:"options";a:1:{s:29:"c_ultimatepdf_line:label:code";N;}}');
INSERT INTO llx_extrafields
    (name, entity, elementtype, list, label, type, size, pos, param)
VALUES
    ('newtitle', 1, 'fichinter', '1', 'DictionaryUltimatepdfTitle', 'sellist', '', 0, 'a:1:{s:7:"options";a:1:{s:30:"c_ultimatepdf_title:label:code";N;}}')
;
INSERT INTO llx_extrafields
    (name, entity, elementtype, list, label, type, size, pos, param)
VALUES
    ('signature', 1, 'fichinter', '1', 'Signature du responsable', 'sellist', '', 0, 'a:1:{s:7:"options";a:1:{s:16:"user:login:rowid";N;}}')
;
INSERT INTO llx_extrafields
    (name, entity, elementtype, list, label, type, size, pos, param)
VALUES
    ('newprice', 1, 'fichinter', '1', 'Shifting', 'price', '', 0, 'a:1:{s:7:"options";a:1:{s:0:"";N;}}');
INSERT INTO llx_extrafields
    (name, entity, elementtype, list, label, type, size, pos, param)
VALUES
    ('newrdv', 1, 'fichinter', '1', 'New RDV', 'radio', '', 0, 'a:1:{s:7:"options";a:2:{i:1;s:3:"Oui";i:2;s:3:"Non";}}');
INSERT INTO llx_extrafields
    (name, entity, elementtype, list, label, type, size, pos, param)
VALUES
    ('newyesno', 1, 'fichinter', '1', 'Montrer commentaire', 'radio', '', 0, 'a:1:{s:7:"options";a:2:{i:1;s:3:"Oui";i:2;s:3:"Non";}}');
INSERT INTO llx_extrafields
    (name, entity, elementtype, list, label, type, size, pos, param)
VALUES
    ('newtitle', 1, 'projet', '1', 'DictionaryUltimatepdfTitle', 'sellist', '', 0, 'a:1:{s:7:"options";a:1:{s:30:"c_ultimatepdf_title:label:code";N;}}')
;



ALTER TABLE llx_propal_extrafields ADD COLUMN newline text NULL;
ALTER TABLE llx_propal_extrafields ADD COLUMN newtitle text NULL;
ALTER TABLE llx_propal_extrafields ADD COLUMN signature text NULL;
ALTER TABLE llx_commande_extrafields ADD COLUMN newline text NULL;
ALTER TABLE llx_commande_extrafields ADD COLUMN newtitle text NULL;
ALTER TABLE llx_commande_extrafields ADD COLUMN signature text NULL;
ALTER TABLE llx_facture_extrafields ADD COLUMN newline text NULL;
ALTER TABLE llx_facture_extrafields ADD COLUMN newtitle text NULL;
ALTER TABLE llx_facture_extrafields ADD COLUMN signature text NULL;
ALTER TABLE llx_commande_fournisseur_extrafields ADD COLUMN signature text NULL;
ALTER TABLE llx_expedition_extrafields ADD COLUMN newline text NULL;
ALTER TABLE llx_expedition_extrafields ADD COLUMN newtitle text NULL;
ALTER TABLE llx_expedition_extrafields ADD COLUMN signature text NULL;
ALTER TABLE llx_fichinter_extrafields ADD COLUMN newline text NULL;
ALTER TABLE llx_fichinter_extrafields ADD COLUMN newtitle text NULL;
ALTER TABLE llx_fichinter_extrafields ADD COLUMN newprice double
(24,8) NULL;
ALTER TABLE llx_fichinter_extrafields ADD COLUMN newrdv text NULL;
ALTER TABLE llx_fichinter_extrafields ADD COLUMN newyesno text NULL;
ALTER TABLE llx_fichinter_extrafields ADD COLUMN signature text NULL;
ALTER TABLE llx_projet_extrafields ADD COLUMN newtitle text NULL;




