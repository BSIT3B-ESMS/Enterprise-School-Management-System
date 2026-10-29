<?php

use Database\Seeders\FacultyRosterSeeder;

it('splits :raw into its name parts', function (string $raw, array $expected) {
    expect(FacultyRosterSeeder::parseName($raw))->toBe($expected);
})->with([
    'a first name and surname' => ['Realiza Marimon', ['first' => 'Realiza', 'middle' => null, 'last' => 'Marimon', 'suffix' => null, 'isDean' => false, 'isSecretary' => false]],
    'a middle initial' => ['Daniel N. Dapis', ['first' => 'Daniel', 'middle' => 'N.', 'last' => 'Dapis', 'suffix' => null, 'isDean' => false, 'isSecretary' => false]],
    'a double initial' => ['Cecile DM. Espiritu', ['first' => 'Cecile', 'middle' => 'DM.', 'last' => 'Espiritu', 'suffix' => null, 'isDean' => false, 'isSecretary' => false]],
    'several given names' => ['Maria Diana Carla R. Quiogue (College Secretary)', ['first' => 'Maria Diana Carla', 'middle' => 'R.', 'last' => 'Quiogue', 'suffix' => null, 'isDean' => false, 'isSecretary' => true]],
    'a dean' => ['Maila N. Unsay (Dean)', ['first' => 'Maila', 'middle' => 'N.', 'last' => 'Unsay', 'suffix' => null, 'isDean' => true, 'isSecretary' => false]],
    'a dean with a degree' => ['Riegie D. Tan (Dean DIT)', ['first' => 'Riegie', 'middle' => 'D.', 'last' => 'Tan', 'suffix' => null, 'isDean' => true, 'isSecretary' => false]],
    'an unclosed bracket' => ['Rajesh Michael J.  Cordova (Dean', ['first' => 'Rajesh Michael', 'middle' => 'J.', 'last' => 'Cordova', 'suffix' => null, 'isDean' => true, 'isSecretary' => false]],
    'a Dr. title' => ['Dr. Carolyn A. Alvero  (Dean)', ['first' => 'Carolyn', 'middle' => 'A.', 'last' => 'Alvero', 'suffix' => null, 'isDean' => true, 'isSecretary' => false]],
    'a degree in brackets' => ['Racquel A. Cortez (MIT, an Engr.)', ['first' => 'Racquel', 'middle' => 'A.', 'last' => 'Cortez', 'suffix' => null, 'isDean' => false, 'isSecretary' => false]],
    'a Jr without a dot' => ['Romeo V. Bawa Jr', ['first' => 'Romeo', 'middle' => 'V.', 'last' => 'Bawa', 'suffix' => 'Jr.', 'isDean' => false, 'isSecretary' => false]],
    'a Jr with a dot' => ['Eugene H. Embalzado Jr.', ['first' => 'Eugene', 'middle' => 'H.', 'last' => 'Embalzado', 'suffix' => 'Jr.', 'isDean' => false, 'isSecretary' => false]],
    'a Dela surname' => ['Glady Dela Cruz', ['first' => 'Glady', 'middle' => null, 'last' => 'Dela Cruz', 'suffix' => null, 'isDean' => false, 'isSecretary' => false]],
    'a Dela surname with an initial' => ['Joseph Wilfred T. Dela Cruz', ['first' => 'Joseph Wilfred', 'middle' => 'T.', 'last' => 'Dela Cruz', 'suffix' => null, 'isDean' => false, 'isSecretary' => false]],
    'a two-word surname' => ['Marilyn T. San Jose (College Secretary)', ['first' => 'Marilyn', 'middle' => 'T.', 'last' => 'San Jose', 'suffix' => null, 'isDean' => false, 'isSecretary' => true]],
    'a name typed in capitals with a degree' => ['EUREKA MAY A. CANON, MBA', ['first' => 'Eureka May', 'middle' => 'A.', 'last' => 'Canon', 'suffix' => null, 'isDean' => false, 'isSecretary' => false]],
    'a capitalized Dela particle' => ['ROWENA R. DE LEON, MBA', ['first' => 'Rowena', 'middle' => 'R.', 'last' => 'De Leon', 'suffix' => null, 'isDean' => false, 'isSecretary' => false]],
    'a degree followed by a note' => ['MARIA NERIZZA GINETH J. ERESO, MBA – HR', ['first' => 'Maria Nerizza Gineth', 'middle' => 'J.', 'last' => 'Ereso', 'suffix' => null, 'isDean' => false, 'isSecretary' => false]],
    'a hyphenated degree' => ['ENRIQUE ANTONIO S. PEREZ, MBA-THM', ['first' => 'Enrique Antonio', 'middle' => 'S.', 'last' => 'Perez', 'suffix' => null, 'isDean' => false, 'isSecretary' => false]],
    'a Chef title' => ['CHEF ENRIQUE LUCES', ['first' => 'Enrique', 'middle' => null, 'last' => 'Luces', 'suffix' => null, 'isDean' => false, 'isSecretary' => false]],
    'a Chef title and a dotted double initial' => ['CHEF RODERICK D.C. TUAZON', ['first' => 'Roderick', 'middle' => 'D.C.', 'last' => 'Tuazon', 'suffix' => null, 'isDean' => false, 'isSecretary' => false]],
    'a Dr. dean' => ['Dr. Michelle E. Flores (Dean)', ['first' => 'Michelle', 'middle' => 'E.', 'last' => 'Flores', 'suffix' => null, 'isDean' => true, 'isSecretary' => false]],
    'a missing space after an initial' => ['Gerald D.Paguio', ['first' => 'Gerald', 'middle' => 'D.', 'last' => 'Paguio', 'suffix' => null, 'isDean' => false, 'isSecretary' => false]],
]);
