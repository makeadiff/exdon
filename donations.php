<?php
require('common.php');

$crud = new Crud("external_donations");
$crud->title = "External Donations";
$crud->allow['add'] = false;
$crud->allow['edit'] = false;

$crud->setListingFields("donation_type", "donor_id", "fundraiser_id", "created_at", "donation_status");
$crud->addListDataField("donor_id", "donours", "Donor", "", array('fields' => 'id,first_name'));
$crud->addListDataField("fundraiser_id", "users", "Fundraiser", "", array('fields' => 'id,CONCAT(first_name, " ", last_name) AS name'));
$crud->render();