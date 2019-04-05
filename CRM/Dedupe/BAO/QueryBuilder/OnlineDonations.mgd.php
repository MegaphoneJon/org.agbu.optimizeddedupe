<?php

// This file declares a managed database record of type "RuleGroup".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference
return [0 =>
  ['name' => 'AGBU_OnlineDetails',
    'entity' => 'RuleGroup',
    'params' =>
    ['version' => 3,
      'contact_type' => 'Individual',
      'threshold' => 8,
      'used' => 'Unsupervised',
      'name' => 'OnlineDonations',
      'title' => 'Online Donations (reserved)',
      'is_reserved' => 1,
    ],
  ],
];

