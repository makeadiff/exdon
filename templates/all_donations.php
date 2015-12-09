<?php
$crud->current_page_data =  $all_donations;
$crud->setListingFields("donation_type", "donation_amount", "amount_deposited", "amount_late", "donor_id", "fundraiser_id", 'donation_status');
$crud->makeListingDisplayData();
$crud->listData();
