<?php
$user = $_SERVER['AUTH_USER'];   // e.g. CORP\jdoe
list($domain, $sam) = explode("\\", $user);

$ldap_server = "ldap://10.1.1.21";
$ldap_port   = 389;
$base_dn   = "DC=cankaya,DC=bel,DC=tr";

$conn = ldap_connect($ldap_host);
ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);

// Either null bind (if PHP runs as a domain account)…
ldap_bind($conn);

// …or service account bind
// ldap_bind($conn, "CORP\\ldapreader", "password");

// Search the user
$filter = "(sAMAccountName=$sam)";
$attrs  = ["cn","mail","distinguishedName"];
$search = ldap_search($conn, $base_dn, $filter, $attrs);
$entries = ldap_get_entries($conn, $search);


echo json_encode($entries);
