<?php
return [
 'enabled'=>(bool)env('EMS_ENABLED',false),
 'auto_sync'=>(bool)env('EMS_AUTO_SYNC',false),
 'base_url'=>env('EMS_BASE_URL'),
 'token'=>env('EMS_API_TOKEN'),
 // Paths and mappings must be copied from EMS's agreed OpenAPI contract.
 'employees_path'=>env('EMS_EMPLOYEES_PATH'),
 'leaves_path'=>env('EMS_LEAVES_PATH'),
 'balances_path'=>env('EMS_BALANCES_PATH'),
 'leave_requests_path'=>env('EMS_LEAVE_REQUESTS_PATH'),
 'employee_fields'=>json_decode(env('EMS_EMPLOYEE_FIELDS','{}'),true)?:[],
 'leave_fields'=>json_decode(env('EMS_LEAVE_FIELDS','{}'),true)?:[],
 'balance_fields'=>json_decode(env('EMS_BALANCE_FIELDS','{}'),true)?:[],
 'leave_request_fields'=>json_decode(env('EMS_LEAVE_REQUEST_FIELDS','{}'),true)?:[],
 // Optional multipart proof field; only set after EMS confirms its signed OpenAPI contract.
 'leave_proof_field'=>env('EMS_LEAVE_PROOF_FIELD'),
];
