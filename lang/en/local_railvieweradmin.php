<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Part of the local_railvieweradmin plugin.
 *
 * @package    local_railvieweradmin
 * @copyright  dpr-rail
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['pluginname'] = 'Railviewer Admin';

// Overlay Defaults Settings
$string['overlay_defaults_settings'] = 'Overlay Default Settings';
$string['overlay_defaults_settings_desc'] = 'Configure the default settings for overlays in the Rail Viewer.';

$string['overlay_default_fontsize'] = 'Default Font Size';
$string['overlay_default_fontsize_desc'] = 'Default font size for overlay texts (in pixels).';

$string['overlay_default_fontcolor'] = 'Default Font Color';
$string['overlay_default_fontcolor_desc'] = 'Default font color for overlay texts (hex code, e.g. #ff0000 for red).';

$string['overlay_default_backgroundcolor'] = 'Default Background Color';
$string['overlay_default_backgroundcolor_desc'] = 'Default background color for overlays (hex code, e.g. #ff0000 for red).';

$string['overlay_default_bordercolor'] = 'Default Border Color';
$string['overlay_default_bordercolor_desc'] = 'Default border color for overlays (hex code, e.g. #00ff00 for green).';

$string['overlay_default_markerposition'] = 'Default Marker Position';
$string['overlay_default_markerposition_desc'] = 'Default position for overlay markers on the screen.';

// Marker Positions
$string['none'] = 'None';
$string['top-left'] = 'Top Left';
$string['top'] = 'Top';
$string['top-right'] = 'Top Right';
$string['right'] = 'Right';
$string['bottom-right'] = 'Bottom Right';
$string['bottom'] = 'Bottom';
$string['bottom-left'] = 'Bottom Left';
$string['left'] = 'Left';

// EBULA API Settings
$string['ebula_api_heading'] = 'EBULA API Settings';
$string['ebula_api_heading_desc'] = 'Configuration of EBULA API connection settings.';

$string['ebula_api_url'] = 'EBULA API URL';
$string['ebula_api_url_desc'] = 'The complete URL to the EBULA API (e.g. https://api.ebula.com).';

$string['ebula_api_user'] = 'EBULA API User';
$string['ebula_api_user_desc'] = 'Username for EBULA API authentication.';

$string['ebula_api_password'] = 'EBULA API Password';
$string['ebula_api_password_desc'] = 'Password for EBULA API authentication.';

$string['ebula_api_key'] = 'EBULA API Key';
$string['ebula_api_key_desc'] = 'API key for EBULA API authentication.';

// Vimeo API Settings
$string['vimeo_api_settings'] = 'Vimeo API Settings';
$string['vimeo_api_settings_desc'] = 'Configuration of Vimeo API connection settings.';

$string['vimeo_access_token'] = 'Vimeo Access Token';
$string['vimeo_access_token_desc'] = 'Access token for Vimeo API authentication.';

$string['vimeo_client_id'] = 'Vimeo Client ID';
$string['vimeo_client_id_desc'] = 'Client ID for the Vimeo API application.';

$string['vimeo_client_secret'] = 'Vimeo Client Secret';
$string['vimeo_client_secret_desc'] = 'Client secret for the Vimeo API application.';

// ignite.video API settings
$string['ignite_api_settings'] = 'ignite.video API settings';
$string['ignite_api_settings_desc'] = 'Configuration of the ignite.video integration (alternative video source to Vimeo).';
$string['ignite_api_key'] = 'ignite.video API key';
$string['ignite_api_key_desc'] = 'API key (bearer token) from the ignite.video "API Integrations" settings.';
$string['ignite_embed_url_template'] = 'ignite.video embed URL template';
$string['ignite_embed_url_template_desc'] = 'Full URL template for the embedded player, with {id} as a placeholder for the video ID (e.g. https://play.ignite.video/player/index.html?id={id}&controls=false, or your own player domain).';
$string['ignite_allowed_statuses'] = 'Allowed ignite.video statuses';
$string['ignite_allowed_statuses_desc'] = 'Only videos with one of the checked statuses are offered in the video picker. If none are checked, "Encoding complete" is used as a fallback.';
$string['ignite_status_complete'] = 'Encoding complete (COMPLETE)';
$string['ignite_status_progressing'] = 'Encoding in progress (PROGRESSING)';
$string['ignite_status_replacing'] = 'File being replaced (REPLACING)';
$string['ignite_status_no_file'] = 'No file uploaded yet (NO_FILE)';
$string['ignite_status_error'] = 'Encoding failed (ERROR)';
$string['ignite_visibility_filter'] = 'ignite.video visibility';
$string['ignite_visibility_filter_desc'] = 'Controls which videos are offered in the video picker based on their visibility (the "enabled/disabled" toggle in ignite.video).';
$string['ignite_visibility_public_only'] = 'Public videos only';
$string['ignite_visibility_private_only'] = 'Private/disabled videos only';
$string['ignite_visibility_both'] = 'Public and private videos';

// SharePoint API settings (see docs/sharepoint-einrichtung.md for the full administrator setup
// guide)
$string['sharepoint_api_settings'] = 'SharePoint API settings';
$string['sharepoint_api_settings_desc'] = 'Configuration of the SharePoint integration (another alternative video source to Vimeo/ignite.video). Requires an Azure AD/Entra app registration with Microsoft Graph access - see the included setup guide (docs/sharepoint-einrichtung.md).';
$string['sharepoint_tenant_id'] = 'Tenant ID (Directory ID)';
$string['sharepoint_tenant_id_desc'] = 'The directory (tenant) ID of your Microsoft 365/Entra tenant, e.g. from the app registration overview page in Azure AD.';
$string['sharepoint_client_id'] = 'Application (client) ID';
$string['sharepoint_client_id_desc'] = 'The application (client) ID of the Azure AD app registration this plugin uses to access SharePoint.';
$string['sharepoint_client_secret'] = 'Client secret';
$string['sharepoint_client_secret_desc'] = 'A client secret created for this app registration (Certificates & secrets). It expires after the chosen validity period - renew it in time before it does.';
$string['sharepoint_site_url'] = 'SharePoint site URL';
$string['sharepoint_site_url_desc'] = 'Full URL of the SharePoint site that holds the videos, e.g. https://yourcompany.sharepoint.com/sites/RouteVideos';
$string['sharepoint_library_name'] = 'Library name (optional)';
$string['sharepoint_library_name_desc'] = 'Name of the document/video library within the site, e.g. "Videos". Leave empty to use the site\'s default document library.';
$string['sharepoint_test_token_failed'] = 'Sign-in to Microsoft Entra failed. Please check the tenant ID, application ID and client secret.';
$string['sharepoint_test_site_failed'] = 'Sign-in succeeded, but the SharePoint site was not found. Please check the site URL and make sure the "Sites.Read.All" application permission has admin consent granted.';
$string['sharepoint_test_drive_failed'] = 'Site found, but the specified library was not found in it. Please check the library name (case-insensitive) or leave it empty to use the default library.';
$string['sharepoint_stream_error'] = 'The video could not be loaded from SharePoint. Please notify an administrator.';

// API Test
$string['api_test'] = 'API Connection Tests';
$string['test_connection'] = 'Test Connection';
$string['api_test_no_credentials'] = 'No credentials configured.';
$string['api_test_success_vimeo'] = 'Connection successful! Connected as: {$a}';
$string['api_test_success_ignite'] = 'Connection successful! Videos in account: {$a}';
$string['api_test_success_ebula'] = 'Connection successful! Credentials accepted (HTTP code: {$a}).';
$string['api_test_success_sharepoint'] = 'Connection successful! Sign-in, site and library were resolved successfully.';
$string['api_test_failed_http'] = 'Connection failed. HTTP code: {$a}';

// Sections Management
$string['manage_sections'] = 'Manage Sections';
$string['sections'] = 'Sections';
$string['back_to_admin'] = 'Back to Admin Page';
$string['create_new_section'] = 'Create New Section';
$string['name'] = 'Name';
$string['duration'] = 'Duration';
$string['actions'] = 'Actions';
$string['edit'] = 'Edit';
$string['delete'] = 'Delete';
$string['confirm_delete'] = 'Really delete?';
$string['confirm_delete_section_used'] = 'Warning: This section is still used by the following route(s): {$a->routes}. These routes have a total of {$a->assignments} user assignment(s). Deleting it will leave a dangling (missing) reference in these routes. Really delete?';
$string['section_used_in_routes'] = 'Used in route(s): {$a}';
$string['edit_overlays'] = 'Edit Overlays';
$string['select_ebula_schedule'] = 'Select Ebula Schedule';
$string['map_ebula_schedule_to_video'] = 'Map Ebula Schedule to Video';

// Ebula Section Map
$string['map_timetable_to_video'] = 'Map Ebula to Video';
$string['current_time'] = 'Current Time';
$string['save_position'] = 'Save Position';
$string['still_image_no_line_movement'] = 'Still Image (Don\'t Move Line)';
$string['browser_no_video_support'] = 'Your browser does not support the video tag.';
$string['browser_no_video_support_du'] = 'Your browser does not support the video tag.';
$string['browser_no_video_support_sie'] = 'Your browser does not support the video tag.';
$string['no_video_found_for_section'] = 'No video found for this section.';
$string['current_video'] = 'Current video';
$string['ignite_encoding_label'] = 'Video is being encoded by ignite.video:';
$string['ignite_encoding_wait_notice'] = 'This page will refresh automatically once encoding is complete.';
$string['ignite_encoding_error'] = 'Encoding failed at ignite.video: {$a}';
$string['ignite_publish_permission_hint'] = 'Note: "Public" only works if the configured ignite.video API key has the "videos.publish" permission in addition to "videos.create". Without it, ignite.video still creates the video as private/disabled - per their own API documentation.';
$string['km'] = 'km';
$string['speed'] = 'Speed';
$string['station'] = 'Station';
$string['delete_error'] = 'Delete Error';
$string['error_loading_mappings'] = 'Error Loading Mappings';
$string['click_table_first_to_choose_position'] = 'Please click on the table first to choose a position.';
$string['point_saved'] = 'Point Saved!';
$string['save_error'] = 'Save Error';

// EBuLa Timetables
$string['ebula_timetables'] = 'EBuLa Timetables';
$string['edit_ebula'] = 'Edit EBuLa timetable';
$string['ebula_xml_content'] = 'XML content';
$string['ebula_updated'] = 'Timetable updated.';
$string['ebula_edit_linked_notice'] = 'Note: {$a} section(s) already use an excerpt taken from this timetable. Changing it here does NOT retroactively affect those already-saved excerpts.';
$string['train_number_cannot_be_empty'] = 'Train number cannot be empty.';
$string['date_cannot_be_empty'] = 'Date cannot be empty.';
$string['train_number_max_5_digits'] = 'Train number can have a maximum of 5 digits.';
$string['date_format_yyyy_mm_dd'] = 'Date must be in YYYY-MM-DD format.';
$string['zip_could_not_be_extracted'] = 'ZIP file could not be extracted.';
$string['error_retrieving_data_http_code'] = 'Error retrieving data. HTTP Code:';
$string['error_retrieving_data_response'] = 'API response:';
$string['retrieve_ebula_timetables'] = 'Retrieve EBuLa Timetables';
$string['ebula_data_imported_successfully'] = 'EBuLa data imported successfully.';
$string['train_number_max_5_digits_label'] = 'Train Number (max 5 digits)';
$string['date_yyyy_mm_dd'] = 'Date (YYYY-MM-DD)';
$string['retrieve'] = 'Retrieve';
$string['saved_entries'] = 'Saved Entries';
$string['no_ebula_data_saved_yet'] = 'No EBuLa data saved yet.';
$string['train_number'] = 'Train Number';
$string['selection_string'] = 'Selection String';
$string['uploaded_at'] = 'Uploaded At';
$string['linked_sections_col'] = 'Linked sections';
$string['no_section_linked'] = 'Not linked to any section';
$string['show'] = 'Show';

// Edit Section
$string['edit_section'] = 'Edit Section';
$string['back_to_list'] = 'Back to List';

// Index EBuLas
$string['ebula_timetable'] = 'EBuLa Timetable';
$string['choose_ebula_timetable'] = 'Choose EBuLa Timetable';
$string['assigned_timetable'] = 'Assigned Timetable';
$string['no_timetable_assigned_yet'] = 'No timetable has been assigned yet';
$string['delete_assigned_timetable'] = 'Delete Assigned Timetable';
$string['confirm_delete_this_section'] = 'Really delete this section?';
$string['available_timetables'] = 'Available Timetables';
$string['no_ebula_timetables_available_yet'] = 'No EBuLa timetables available yet.';
$string['select'] = 'Select';
$string['delete_existing_assignment_first'] = 'You must first delete the existing assignment before you can create a new assignment';

// Common strings
$string['save'] = 'Save';
$string['cancel'] = 'Cancel';
$string['delete'] = 'Delete';
$string['edit'] = 'Edit';
$string['add'] = 'Add';
$string['yes'] = 'Yes';
$string['no'] = 'No';
$string['none'] = 'None';
$string['required_field'] = 'Required field';

// Ebula related strings
$string['select_ebula'] = 'Select Ebula';
$string['show_ebula'] = 'Show Ebula';
$string['select_ebula_for_section'] = 'Select Ebula for section {$a}';
$string['save_selected_timetable'] = 'Save selected timetable';
$string['timetable_saved'] = 'Timetable saved (ID: {$a})';
$string['save_error'] = 'Error saving: {$a}';

// XML validation strings
$string['xml_string_empty'] = 'XML string is empty.';
$string['xml_parse_error'] = 'Error parsing XML:';
$string['xml_not_valid_schema'] = 'XML is **not** valid against the schema:';
$string['xml_valid_schema'] = 'XML file is valid according to the schema.';
$string['xml_simplexml_load_error'] = 'XML could not be loaded with SimpleXML:';

// Table headers and content
$string['bfpl_tables'] = 'BfplTables';
$string['overview'] = 'Overview';
$string['track_position'] = 'Track position: {$a}';
$string['km'] = 'km';
$string['speed'] = 'Speed';
$string['operating_point'] = 'Operating point';
$string['route'] = 'Route';
$string['routes'] = 'Routes/places (multiple selection possible)';
$string['einsatzstelle'] = 'Duty station';
$string['einsatzstelle_assign'] = 'Assign duty station';
$string['einsatzstelle_assign_help'] = 'Assigns all routes/places of this duty station in addition to any individually selected above. Can be combined with a manual selection.';
$string['no_einsatzstelle'] = '— none —';
$string['select_route_or_einsatzstelle'] = 'Please select at least one route/place or a duty station.';
$string['distance'] = 'Distance';
$string['type_entry'] = 'Type entry';
$string['gradient'] = 'Gradient';
$string['radius'] = 'Radius';
$string['track_position_short'] = 'Track pos.';
$string['width'] = 'Width';
$string['length'] = 'Length';
$string['info'] = 'Info';
$string['saw_lines'] = 'Saw lines';
$string['branch'] = 'Branch';
$string['continuation'] = 'Continuation';

// Track positions
$string['left'] = 'Left';
$string['right'] = 'Right';
$string['both_tracks'] = 'Both tracks';
$string['single_track'] = 'Single track';

// Overlay form strings
$string['overlay_name'] = 'Overlay Name';
$string['section'] = 'Section';
$string['start_time'] = 'Start time (seconds)';
$string['end_time'] = 'End time (seconds)';
$string['position_x'] = 'Position X (% of width)';
$string['position_y'] = 'Position Y (% of height)';
$string['show_advanced_options'] = 'Show advanced options';
$string['hide_advanced_options'] = 'Hide advanced options';
$string['font_size'] = 'Font size (px)';
$string['font_color'] = 'Font color (HEX)';
$string['background_color'] = 'Background color (HEX)';
$string['border_color'] = 'Border color (HEX)';
$string['marker_position'] = 'Marker position';
$string['fixed_bubble_width'] = 'Fixed bubble width (%)';

// Marker positions
$string['no_arrow'] = 'No arrow';
$string['top_left'] = 'Top left';
$string['top'] = 'Top center';
$string['top_right'] = 'Top right';
$string['right'] = 'Right center';
$string['bottom_right'] = 'Bottom right';
$string['bottom'] = 'Bottom center';
$string['bottom_left'] = 'Bottom left';
$string['left'] = 'Left center';

// Validation messages
$string['end_time_validation'] = 'End time must be greater than or equal to start time.';
$string['fixed_width_validation'] = 'Please enter a number between 0 and 100 or leave the field empty.';
$string['name_required'] = 'Please enter a name.';
$string['vimeo_video_required'] = 'Please select a Vimeo video.';

// Section form strings
$string['name'] = 'Name';
$string['description'] = 'Description';
$string['video_source'] = 'Video source';
$string['upload_file'] = 'Upload file';
$string['select_vimeo_video'] = 'Select Vimeo video';
$string['select_ignite_video'] = 'Select ignite.video video';
$string['select_ignite_video_label'] = 'Select ignite.video video:';
$string['ignite_video_required'] = 'Please select an ignite.video video.';
$string['ignite_id'] = 'ignite.video ID: {$a}';
$string['select_sharepoint_video'] = 'Select SharePoint video';
$string['select_sharepoint_video_label'] = 'Select SharePoint video:';
$string['sharepoint_video_required'] = 'Please select a SharePoint video.';
$string['sharepoint_id'] = 'SharePoint video item selected';
$string['upload_video_file'] = 'Upload video file';
$string['select_vimeo_video_label'] = 'Select Vimeo video:';
$string['search_videos'] = 'Search videos...';
$string['load_videos'] = 'Load videos';
$string['refresh_videos'] = 'Refresh';
$string['loading_videos'] = 'Loading videos...';
$string['no_description'] = 'No description';
$string['created'] = 'Created: {$a}';
$string['select_video'] = 'Select';
$string['selected_video'] = 'Selected video:';
$string['vimeo_id'] = 'Vimeo ID: {$a}';
$string['previous'] = 'Previous';
$string['next'] = 'Next';

// JavaScript alert messages
$string['js_timetable_saved'] = '✅ Timetable saved (ID: {$a})';
$string['js_save_error'] = 'Error saving: {$a}';

// Error messages
$string['error_loading_videos'] = 'Error loading videos';
$string['error_parsing_response'] = 'Error parsing response';
$string['error_loading_videos_status'] = 'Error loading videos. Status: {$a}';

// Font size options (generated dynamically, but labels for reference)
$string['font_size_px'] = '{$a} px';

// Additional context strings
$string['already_selected'] = 'Already selected';
$string['no_thumbnail'] = 'No thumbnail available';

// Security/error messages for AJAX endpoints
$string['invalid_session'] = 'Invalid or expired session. Please reload the page and try again.';
$string['generic_ajax_error'] = 'An error occurred. Please try again.';
$string['invalid_data'] = 'Invalid data.';

// Ebula section map (previously missing strings, were hardcoded)
$string['no_input_received'] = 'No data received.';
$string['json_decode_error'] = 'The submitted data could not be read.';
$string['no_id_provided'] = 'No ID provided.';
$string['record_deleted'] = 'Record deleted.';
$string['record_not_found'] = 'Record not found.';
$string['time_seconds'] = 'Time (sec.)';
$string['position_px'] = 'Position (px)';
$string['is_static'] = 'Static?';
$string['no_points_saved'] = 'No points saved.';
$string['sectionid_missing'] = 'Section ID missing.';

// Addressing setting (informal/formal), applies to both Railviewer plugins
$string['addressing'] = 'Form of address';
$string['addressing_desc'] = 'Controls whether users are addressed informally or formally in the Railviewer activity module and in this admin interface.';
$string['addressing_sie'] = 'Formal ("Sie")';
$string['addressing_du'] = 'Informal ("Du")';

$string['overlay_updated'] = 'Overlay updated successfully';
$string['overlay_created'] = 'Overlay created successfully';
$string['section_saved'] = 'Section saved.';

// Stops (railviewer_stops)
$string['stops_for_section'] = 'Stops for section: {$a}';
$string['back_to_section_overview'] = 'Back to section overview';
$string['add_new_stop'] = 'Add new stop';
$string['time_of_stop_seconds'] = 'Time of stop (sec.)';
$string['no_stops_for_section'] = 'No stops for this section yet.';
$string['edit_stop'] = 'Edit stop';
$string['create_new_stop'] = 'Create new stop';
$string['stop_name'] = 'Stop name';
$string['stop_time_seconds'] = 'Time of the stop (seconds)';
$string['preview'] = 'Preview';
$string['stop_reached_default_name'] = 'Stop reached';
$string['video_stopped_at_template'] = 'Video stopped at "__NAME__" (__TIME__s).';
$string['stop_saved'] = 'Stop saved.';
$string['stoptime_must_be_positive'] = 'The time must not be negative.';

// assignment_form.php / route_form.php
$string['user'] = 'User';
$string['users'] = 'Users (multiple selection possible)';
$string['assignments_created_some_skipped'] = '{$a->created} assignment(s) created, {$a->skipped} skipped (already assigned to this route).';
$string['assignments_all_skipped'] = 'All selected users were already assigned to this route - nothing was changed.';
$string['required_views'] = 'Required views';
$string['assign_both_directions'] = 'Also assign the return direction';
$string['assign_both_directions_help'] = 'If the selected route is linked as an outbound/return pair with another route, that return-direction route is also assigned in one step, with the same number of required views. Has no effect if the route has no linked return direction.';
$string['must_be_positive_number'] = 'Must be a positive number';
$string['select_sections_order'] = 'Select sections (note order)';
$string['select_at_least_one_section'] = 'Please select at least one section.';

// index.php / assignments.php / edit_assignment.php / edit_route.php / index_overlays.php
$string['adminpagetitle'] = 'Railviewer route management';
$string['adminpagedescription'] = 'Manage sections, add overlays and combine sections into routes. Assign routes to individual users and define how often they need to watch them.';
$string['assemble_routes'] = 'Assemble routes';
$string['manage_overlays_nav'] = 'Manage overlays';
$string['assign_routes_to_users'] = 'Assign routes to users';
$string['tile_sections_desc'] = 'Create video sections, set duration and source.';
$string['tile_routes_desc'] = 'Combine sections into routes in the right order.';
$string['tile_overlays_desc'] = 'Add text overlays and stops to sections.';
$string['tile_assignments_desc'] = 'Assign routes to individual users and track progress.';
$string['tile_ebula_desc'] = 'Retrieve and store EBuLa timetable data from the DB API.';
$string['playback_settings'] = 'Playback';
$string['playback_settings_desc'] = 'Settings for the custom video progress bar in mod_railviewer/play.php.';
$string['max_playback_speed'] = 'Maximum playback speed';
$string['max_playback_speed_desc'] = 'Highest speed level users can select in the player (up to 8×). Only available for upload and Vimeo videos - ignite.video does not support playback speed control. "Off" (1×) hides the speed control entirely.';
$string['max_playback_speed_off'] = 'Off (1× only - no control)';
$string['show_mute_button'] = 'Show mute/volume control';
$string['show_mute_button_desc'] = 'Shows a mute/unmute button in the custom video progress bar. When disabled, audio always plays at the device\'s standard volume, without a mute option in the player itself.';
$string['settings_tile_title'] = 'Settings';
$string['tile_settings_desc'] = 'API credentials, addressing, map integration and other plugin settings.';
$string['manage_assignments'] = 'Manage route assignments';
$string['add_assignment'] = '➕ Add assignment';
$string['assign_routes_to_user'] = '➕ Assign routes to this user';
$string['view_user_map'] = 'View map';
$string['progress_col'] = 'Progress';
$string['required_col'] = 'Required';
$string['status'] = 'Status';
$string['status_done'] = 'Done';
$string['status_open'] = 'Open';
$string['stat_users'] = 'Users';
$string['stat_assignments'] = 'Assignments';
$string['stat_completed'] = 'Completed';
$string['stat_open'] = 'Open';
$string['search_assignments'] = 'Search by user…';
$string['search_routes'] = 'Search by name or section…';
$string['search_routes_empty'] = 'No routes found.';
$string['no_assignments_yet'] = 'No assignments yet.';
$string['round_trip_progress'] = '{$a->completed} / {$a->required} combined (outbound + return)';
$string['edit_assignment_title'] = 'Edit assignment';
$string['create_new_assignment'] = 'Create new assignment';
$string['manage_routes_title'] = 'Manage routes';
$string['manage_einsatzstellen'] = 'Manage duty stations';
$string['create_new_einsatzstelle'] = 'Create new duty station';
$string['edit_einsatzstelle'] = 'Edit duty station';
$string['einsatzstelle_routes'] = 'Routes/places for this duty station';
$string['einsatzstelle_saved'] = 'Duty station saved.';
$string['no_einsatzstellen_yet'] = 'No duty stations yet.';
$string['select_at_least_one_route'] = 'Please select at least one route/place.';
$string['tile_einsatzstellen_desc'] = 'Bundle several routes/places into duty stations to assign them together.';
$string['create_new_route'] = 'Create new route';
$string['route_sections_col'] = 'Sections';
$string['missing_sections_warning'] = '{$a} missing section(s)';
$string['missing_sections_warning_title'] = 'This route references at least one section that no longer exists (likely deleted directly). Please clean this up in the route editor.';
$string['route_pairing_col'] = 'Return direction';
$string['paired_route'] = 'Return direction (linked route)';
$string['paired_route_help'] = 'Symmetrically links this route with another, already existing route as its outbound/return counterpart. If the chosen route is already linked to a third route, that old link is automatically removed (the third route itself is kept, just becomes unlinked again).';
$string['no_paired_route'] = '— none —';
$string['route_direction'] = 'Direction of travel';
$string['route_direction_required'] = 'Please choose a direction of travel when linking a return-direction route.';
$string['route_pairing_display'] = '{$a->direction}: linked with "{$a->partnername}"';
$string['route_unlinked'] = 'Link removed.';
$string['unlink_route'] = 'Unlink';
$string['overlay_sections_title'] = 'Overlay sections';
$string['overlay_count_col'] = 'Number of overlays';
$string['create_new_overlay'] = 'Create new overlay';
$string['assignment_saved'] = 'Assignment saved.';
$string['route_saved'] = 'Route saved.';
$string['edit_route'] = 'Edit route';
$string['fahrwegpunkt_art_we'] = 'WE';
$string['overlays_for_section'] = 'Overlays for section: {$a}';
$string['no_overlays_for_section'] = 'No overlays for this section yet.';
$string['edit_overlay'] = 'Edit overlay';

// Route map widget.
$string['map_settings'] = 'Map integration';
$string['map_settings_desc'] = 'Settings for the optional route map view (Leaflet map of the German rail network).';
$string['map_enabled'] = 'Enable map view';
$string['map_enabled_desc'] = 'Turns the entire map feature (admin map management and the map view for users) on or off. Disabled by default until the tile server and geodata have been reviewed.';
$string['map_tile_url'] = 'Map tile URL';
$string['map_tile_url_desc'] = 'URL template of the map tile server (WMTS/XYZ) with the {z}, {x}, {y} placeholders. Defaults to the privacy-friendly TopPlusOpen service by the German Federal Agency for Cartography and Geodesy (BKG), but can be switched to any other provider or a self-hosted server at any time.';
$string['map_tile_attribution'] = 'Attribution text';
$string['map_tile_attribution_desc'] = 'Shown at the bottom of the map. Please check it against the current terms of use of the chosen map service before going into production.';
$string['map_tile_max_zoom'] = 'Maximum zoom level';
$string['map_tile_max_zoom_desc'] = 'Highest zoom level requested from the map.';
$string['streckenmap'] = 'Route map';
$string['tile_streckenmap_desc'] = 'Assign videos to route sections or single locations by clicking on the map.';
$string['map_disabled_notice'] = 'The map view is currently disabled.';
$string['map_no_tileurl_notice'] = 'No map tile server is configured.';
$string['map_no_geo_sections_notice'] = 'No map-based sections have been set up for your assigned routes yet.';
$string['streckenmap_admin_intro'] = 'Assign videos directly on the map to a route section or a single location.';
$string['streckenmap_selection_heading'] = 'Selection';
$string['streckenmap_admin_hint'] = 'Click edges to build a connected chain for a multi-part video, or click directly on a location marker for a local-knowledge video.';
$string['streckenmap_current_chain'] = 'Current chain';
$string['streckenmap_remove_last'] = 'Remove last edge';
$string['streckenmap_discard_chain'] = 'Discard selection';
$string['streckenmap_continue'] = 'Continue';
$string['streckenmap_node_selected'] = 'Selected location';
$string['streckenmap_legend_unassigned'] = 'No video assigned';
$string['streckenmap_legend_assigned'] = 'Video assigned';
$string['streckenmap_legend_selected'] = 'Currently selected';
$string['streckenmap_chain_conflict'] = 'Part of this chain is already assigned to another section. Please adjust your selection.';
$string['streckenmap_linked'] = 'Map link';
$string['streckenmap_linked_edges'] = 'This section is linked to {$a} edge(s) on the route map.';
$string['streckenmap_linked_node'] = 'This section is linked to the location "{$a}" on the route map (local knowledge).';
$string['direction_hin'] = 'Outbound';
$string['direction_rueck'] = 'Return';
$string['route_label_short'] = 'Route';
$string['ortskenntnis'] = 'Local knowledge';

// Video upload directly to Vimeo/ignite.video (classes/forms/section_form.php)
$string['mode_search'] = 'Search video';
$string['mode_upload'] = 'Upload video';
$string['upload_title'] = 'Title';
$string['upload_status'] = 'Status';
$string['upload_status_public'] = 'Public';
$string['upload_status_unlisted'] = 'Unlisted';
$string['upload_status_private'] = 'Private';
$string['upload_start'] = 'Upload';
$string['upload_size_hint'] = 'Note: the maximum file size per upload is limited by the server.';
$string['upload_in_progress'] = 'Uploading video, please wait...';
$string['upload_success'] = 'Video uploaded successfully and selected for this section.';
$string['upload_title_required'] = 'Please enter a title for the video.';
$string['upload_no_file'] = 'Please select a video file.';
$string['upload_file_too_large'] = 'The file is too large for upload (server-side limit exceeded).';
$string['upload_network_error'] = 'Network error during upload. Please check your internet connection and try again.';
$string['video_source_not_configured_suffix'] = '(no longer configured)';
$string['upload_source_heading'] = 'Video source: file upload';
$string['upload_source_heading_desc'] = 'Settings for using Moodle\'s own file storage as a video source for route sections.';
$string['enable_upload_source'] = 'Offer file upload as a video source';
$string['enable_upload_source_desc'] = 'Controls whether "Upload file" (Moodle\'s own file storage) is offered as a video source when creating/editing a section. Usually an admin sets up only a single video source - disable this if only Vimeo/ignite.video/SharePoint should be used. Existing sections that already use file upload are not affected.';

// S3 object storage.
$string['s3_settings'] = 'S3 object storage';
$string['s3_settings_desc'] = 'Store videos in an S3-compatible bucket (e.g. Hetzner Object Storage). Keep the bucket private; videos are played via short-lived signed URLs. For browser uploads the bucket needs a CORS rule allowing PUT from this site\'s origin.';
$string['s3_endpoint'] = 'Endpoint';
$string['s3_endpoint_desc'] = 'e.g. https://fsn1.your-objectstorage.com';
$string['s3_region'] = 'Region';
$string['s3_region_desc'] = 'Used for request signing, e.g. fsn1, nbg1 or hel1 for Hetzner.';
$string['s3_bucket'] = 'Bucket name';
$string['s3_bucket_desc'] = 'Name of the bucket holding the videos.';
$string['s3_access_key'] = 'Access key';
$string['s3_secret_key'] = 'Secret key';
$string['s3_pathstyle'] = 'Path-style addressing';
$string['s3_pathstyle_desc'] = 'Use endpoint/bucket/key instead of bucket.endpoint/key. Recommended.';
$string['s3_url_expiry_hours'] = 'Signed URL lifetime (hours)';
$string['s3_url_expiry_hours_desc'] = 'How long playback URLs stay valid (1-168). Should exceed the longest viewing session.';
$string['s3_test_ok'] = 'Connection successful, bucket is reachable.';
$string['s3_not_configured'] = 'S3 storage is not fully configured.';
$string['s3_request_failed'] = 'S3 request failed: {$a}';
$string['s3_upload_invalid'] = 'Invalid file name or S3 not configured (allowed: mp4, m4v, webm, ogv, mov).';
$string['select_s3_video'] = 'S3 object storage';
$string['s3_select_label'] = 'Select video from S3 bucket';
$string['s3_filter_prefix'] = 'Folder / prefix filter';
$string['s3_load_more'] = 'Load more';
$string['s3_upload_label'] = 'Or upload a new video to the bucket';
$string['s3_uploading'] = 'Uploading...';
$string['s3_upload_failed'] = 'Upload failed (check bucket CORS rule).';
$string['s3_video_required'] = 'Please select or upload a video from S3.';
$string['s3_selected'] = 'Selected S3 object';
$string['s3_upload_access_key'] = 'Access key for uploads only (optional)';
$string['s3_upload_secret_key'] = 'Secret key for uploads only (optional)';
$string['s3_upload_keys_desc'] = 'Separate credentials for browser uploads, e.g. a key with write permission only. Leave both empty to use the general credentials above for uploads too. Listing and playback always use the general credentials.';

// Berechtigungen, Events, Webhook
$string['railvieweradmin:managesettings'] = 'Manage Railviewer settings (credentials, webhook)';
$string['railvieweradmin:editsections'] = 'Create and edit routes/sections, retrieve EBuLa timetables';
$string['railvieweradmin:deletesections'] = 'Delete routes/sections, overlays and EBuLa data';
$string['railvieweradmin:manageeinsatzstellen'] = 'Manage duty stations';
$string['railvieweradmin:assignroutes'] = 'Assign routes to users';
$string['railvieweradmin:viewprogress'] = 'View user progress (API)';
$string['event_route_completed'] = 'Assigned route completed';
$string['event_all_routes_completed'] = 'All assigned routes completed';
$string['webhook_settings'] = 'Webhook (progress notification)';
$string['webhook_settings_desc'] = 'Sends an HTTP POST (JSON) when a user has completed an assigned route (route_completed) or all assigned routes (all_routes_completed) the required number of times. Delivery runs through Moodle cron tasks and is retried automatically on failure.';
$string['webhook_url'] = 'Webhook URL';
$string['webhook_url_desc'] = 'Target URL for progress events. Leave empty to disable the webhook.';
$string['webhook_secret'] = 'Webhook secret';
$string['webhook_secret_desc'] = 'Optional. If set, the header X-Railviewer-Signature contains "sha256=" + HMAC-SHA256 of the request body.';
$string['webhook_failed'] = 'Webhook delivery failed: {$a}';
$string['api_test_success_webhook'] = 'Connection successful! Target answered with HTTP {$a}.';

$string['learning_settings'] = 'Learning methods';
$string['learning_settings_desc'] = 'Optional feature: additional learning methods (self-driving, ride-along, at night …) with trainer confirmation, a requirements matrix per route and experience type, and compliance reports.';
$string['learning_methods_enabled'] = 'Enable learning methods';
$string['learning_methods_enabled_desc'] = 'Off: only watching the route video counts, as before. On: requirements (video and further methods) are defined per route and experience type and copied when assigning.';
$string['compliance_mail_enabled'] = 'Send completion report by email automatically';
$string['compliance_mail_enabled_desc'] = 'As soon as an employee has completed all assigned routes, the target/actual report is sent as a PDF to the addresses below (once per employee).';
$string['compliance_mail_to'] = 'Recipient addresses';
$string['compliance_mail_to_desc'] = 'One or more email addresses, comma-separated. The report contains personal data and signatures – use internal addresses only.';
$string['compliance_mail_test_subject'] = 'Railviewer: compliance mail test';
$string['compliance_mail_test_body'] = 'This is a test message from the Railviewer settings.';
$string['compliance_mail_test_ok'] = 'Test mail sent to: {$a}';
$string['compliance_mail_test_failed'] = 'The test mail could not be sent.';
$string['learning_methods'] = 'Learning methods';
$string['learning_methods_desc'] = 'Further learning methods besides the route video. The required frequency per route and experience type is set on the route itself.';
$string['learning_feature_disabled'] = 'The learning methods feature is not enabled in the plugin settings.';
$string['method_night'] = 'Night ride';
$string['method_in_use'] = 'The method is in use and cannot be deleted. Disable it instead.';
$string['add_method'] = 'Add learning method';
$string['experience_types'] = 'Experience types';
$string['experience_types_desc'] = 'The type is chosen when assigning and determines which requirements of the route\'s matrix apply.';
$string['experience_type_in_use'] = 'The type is in use and cannot be deleted. Disable it instead.';
$string['add_experience_type'] = 'Add experience type';
$string['sortorder'] = 'Order';
$string['enabled'] = 'Enabled';
$string['record_saved'] = 'Saved.';

$string['route_requirements'] = 'Requirements';
$string['route_requirements_desc'] = 'Defines what is required for this route: count per learning method and experience type, target duration per section and substitution rules. Changes apply to new assignments; existing ones keep the requirements copied when assigned.';
$string['back_to_routes'] = 'Back to routes';
$string['route_has_no_sections'] = 'This route has no sections yet.';
$string['method_video'] = 'Video';
$string['requirement_matrix'] = 'Requirement matrix';
$string['requirement_matrix_desc'] = 'Required count per section (video: runs of the whole route). Empty or 0 = not required.';
$string['experience_type'] = 'Experience type';
$string['section_durations'] = 'Target duration per section (minutes per run)';
$string['section_durations_desc'] = 'Leave empty to use the section\'s video duration (shown in grey). The duration is stored on the section and applies to every route containing it.';
$string['substitution_rules'] = 'Substitution rules';
$string['substitution_rules_desc'] = 'Rides of the substituting method count towards the replaced method, but only rides exceeding its own requirement (e.g. 1× night replaceable by 2× day). Unchecked: the user decides whether to apply the rule.';
$string['subst_replaced'] = 'Replaceable method';
$string['subst_by'] = 'Replaced by';
$string['subst_factor'] = 'Count (N×)';
$string['subst_auto'] = 'Apply automatically';
$string['subst_invalid'] = 'Invalid substitution rule (same method, missing selection or count below 1).';
$string['subst_cycle'] = 'Mutual substitution (A by B and B by A) is not allowed.';

$string['experience_type_help'] = 'Experience type';
$string['experience_type_help_help'] = 'The type determines which requirements of each route\'s matrix apply to this assignment.';
$string['assign_review_title'] = 'Review assignment';
$string['assign_review_desc'] = 'Untick routes that should not be required (e.g. because the route is already known). A reason is mandatory for unticked routes; they are recorded as waived and count as fulfilled.';
$string['assign_keep'] = 'Require';
$string['assign_existing_hint'] = 'Note';
$string['assign_existing_merge'] = '{$a} users already have this route – requirements are combined (maximum)';
$string['waive_reason'] = 'Reason (if unticked)';
$string['waive_reason_placeholder'] = 'e.g. already knows the route';
$string['waive_reason_required'] = 'Please enter a reason for the unticked route “{$a}”.';
$string['assign_confirm'] = 'Apply assignment';
$string['assignments_applied'] = 'Assignment applied: {$a->created} new, {$a->merged} merged, {$a->exists} unchanged.';

$string['add_ride'] = 'Record a ride';
$string['compliance_user_link'] = 'Compliance';
$string['declaration_enabled'] = 'Require completion declaration';
$string['declaration_enabled_desc'] = 'After all learning rides are done, the learner must confirm (or decline) a declaration before the whole order counts as done. The completion event, webhook and compliance mail follow only after confirmation.';
$string['declaration_text'] = 'Completion declaration text';
$string['declaration_text_desc'] = 'Formatted text. Placeholders: {firstname}, {lastname}, {fullname}, {idnumber}, {routes}, {einsatzstellen}, {duration} (target total duration), {date}.';
$string['declaration_text_default'] = '<p>I, {fullname}, declare that I have acquired route knowledge for the routes {routes} (operating sites: {einsatzstellen}) and that the learning rides were recorded truthfully.</p>';
$string['declaration_title'] = 'Completion declaration';
$string['declaration_declined_hint'] = 'You declined the declaration. The order only counts as done once you confirm it.';
$string['declaration_decline_reason'] = 'Reason if declined (optional)';
$string['declaration_accept_btn'] = 'Confirm declaration';
$string['declaration_decline_btn'] = 'Decline declaration';
$string['declaration_accepted_msg'] = 'Declaration confirmed. The order is complete.';
$string['declaration_declined_msg'] = 'Declaration declined. The order stays open.';
$string['declaration_not_open'] = 'There is currently no declaration to answer.';
$string['order_complete'] = 'The whole order is complete.';
$string['duration_incomplete'] = '(No duration is set for some sections/methods.)';
$string['duration_summary'] = 'Total order duration: target {$a->soll} · done {$a->ist} · remaining {$a->rest} ({$a->percent} %)';
$string['matrix_missing'] = '{$a} more needed';
$string['matrix_pending'] = '({$a} awaiting confirmation)';
$string['matrix_substituted'] = '(+{$a} substituted)';
$string['video_progress'] = 'Route video: {$a->completed} / {$a->required} runs';
$string['still_missing'] = 'Still missing';
$string['waived_label'] = 'Waived';
$string['my_einsatzstellen'] = 'My operating sites';
$string['my_progress'] = 'My progress';
$string['my_rides'] = 'My learning rides';
$string['my_rides_list'] = 'Recorded rides';
$string['no_rides_yet'] = 'No rides recorded yet.';
$string['rides_to_confirm'] = 'Rides awaiting your confirmation';
$string['ride_confirm_title'] = 'Confirm learning ride';
$string['ride_already_answered'] = 'This ride has already been answered.';
$string['ride_confirm_btn'] = 'Confirm';
$string['ride_reject_btn'] = 'Reject';
$string['ride_reject_reason'] = 'Reason if rejected (optional)';
$string['ride_rider'] = 'Rider';
$string['ride_sections'] = 'Sections ridden';
$string['ride_method'] = 'Learning method';
$string['ride_date'] = 'Date';
$string['ride_train'] = 'Train number';
$string['ride_scope'] = 'Scope';
$string['ride_scope_full'] = 'Whole route';
$string['ride_scope_part'] = 'Partial section (from–to)';
$string['ride_from'] = 'From section';
$string['ride_to'] = 'To section';
$string['ride_trainer'] = 'Trainer';
$string['ride_trainer_placeholder'] = 'Type a name (at least 2 characters) …';
$string['ride_external'] = 'Trainer belongs to another railway undertaking (EVU)';
$string['ride_evu'] = 'EVU';
$string['ride_trainername'] = 'Name of the instructing person';
$string['ride_signature'] = 'Signature of the instructing person';
$string['ride_signature_clear'] = 'Clear';
$string['ride_save'] = 'Save ride';
$string['ride_saved_pending'] = 'Ride saved. The trainer has been notified to confirm.';
$string['ride_saved_external'] = 'Ride saved with signature and counted.';
$string['ride_confirmed_msg'] = 'Ride confirmed.';
$string['ride_rejected_msg'] = 'Ride rejected.';
$string['ride_invalid'] = 'Invalid ride (assignment or method).';
$string['ride_invalid_range'] = 'Invalid section range (respect the route order).';
$string['ride_invalid_train'] = 'Please enter a train number (max. 30 characters).';
$string['ride_invalid_date'] = 'Invalid date (not in the future).';
$string['ride_invalid_trainer'] = 'Please pick a trainer from the search results.';
$string['ride_external_required'] = 'EVU and name are required for external trainers.';
$string['ride_signature_required'] = 'Please draw a signature.';
$string['ride_cannot_respond'] = 'You cannot (or no longer) answer this ride.';
$string['ride_status_pending'] = 'Pending';
$string['ride_status_confirmed'] = 'Confirmed';
$string['ride_status_rejected'] = 'Rejected';
$string['ride_status_revoked'] = 'Revoked';
$string['ride_message_subject'] = 'Please confirm learning ride: {$a->rider} ({$a->date})';
$string['ride_message_body'] = '{$a->rider} recorded a learning ride and named you as trainer.

Route: {$a->route}
Method: {$a->method}
Date: {$a->date}
Train number: {$a->train}

Please confirm or reject the ride here: {$a->url}';
$string['ride_decision_confirmed'] = '{$a->trainer} confirmed your ride of {$a->date} (train {$a->train}).';
$string['ride_decision_rejected'] = '{$a->trainer} rejected your ride of {$a->date} (train {$a->train}).';
$string['messageprovider:ridestoconfirm'] = 'Learning ride awaiting confirmation';
$string['messageprovider:ridedecision'] = 'Decision on a learning ride';
$string['subst_apply_label'] = 'Substitution: {$a->a} replaceable by {$a->n}× {$a->b}';
$string['subst_on'] = 'applied';
$string['subst_off'] = 'not applied';
$string['subst_enable'] = 'Apply substitution';
$string['subst_disable'] = 'Do not apply';

$string['compliance_download_pdf'] = 'Download PDF';
$string['compliance_filter_desc'] = 'Choose experience types and operating sites (empty = all). Each type becomes its own section; in the PDF the types follow one another.';
$string['compliance_generated'] = 'Created on {$a}';
$string['compliance_mail_subject'] = 'Railviewer: order completed – {$a->name}';
$string['compliance_mail_body'] = '{$a->name} completed all assigned routes on {$a->date}. The target/actual report is attached as a PDF.';
$string['compliance_mail_sent_msg'] = 'Report has been sent.';
$string['compliance_mail_to_user'] = 'Also send the report to the employee\'s own email address';
$string['compliance_mail_to_user_desc'] = 'In addition to the addresses above, the completion report is sent to the email address in the employee\'s Moodle profile.';
$string['compliance_mail_failed'] = 'The compliance report could not be sent.';
$string['compliance_preview'] = 'Preview';
$string['compliance_requirements_title'] = 'Compliance matrix (target requirements)';
$string['compliance_send_now'] = 'Send report now';
$string['compliance_soll_ist'] = 'Target | Actual';
$string['compliance_substituted'] = '{$a} substituted';
$string['compliance_total_duration'] = 'Total target duration';
$string['compliance_user_pick'] = 'Search employee (name or username):';
$string['compliance_user_title'] = 'Compliance report route/location knowledge target/actual';
$string['declaration_status_accepted'] = 'Declaration confirmed on {$a}.';
$string['declaration_status_declined'] = 'Declaration declined on {$a}.';
$string['declaration_status_none'] = 'The required completion declaration has not been given yet.';
$string['order_open'] = 'The order is not yet fully completed.';
$string['einsatzstellen'] = 'Operating sites';
$string['ride_revoke'] = 'Revoke';
$string['ride_revoked_msg'] = 'Ride revoked.';
$string['rides_revoke_heading'] = 'Confirmed rides (can be revoked)';

$string['tile_methods_desc'] = 'Maintain further learning methods (self-driving, ride-along, at night …).';
$string['tile_types_desc'] = 'Maintain experience types for assignments.';
$string['tile_compliance_desc'] = 'Target matrix routes × operating sites × learning methods as PDF.';
$string['tile_compliance_user_desc'] = 'View, download or send a target/actual report for an employee.';
$string['tile_import_desc'] = 'Import master data, routes, requirements (number of learning rides), substitutions, operating sites and assignments from CSV/ZIP, incl. export to complete.';

$string['import_sections_title'] = 'Import sections from CSV';
$string['import_sections_desc'] = 'CSV with header row. Columns: name (required), description, routename (assembles the sections into this route in CSV order), video_source (vimeo, ignite, sharepoint or s3) and video_ref (Vimeo/ignite ID, SharePoint “driveId|itemId” or S3 path). File-upload videos are not possible via CSV; sections without video data can be completed later.';
$string['import_sample'] = 'Download sample CSV';
$string['import_csvfile'] = 'CSV file';
$string['import_delimiter'] = 'Delimiter';
$string['import_encoding'] = 'Encoding';
$string['import_preview'] = 'Preview';
$string['import_preview_summary'] = '{$a->ok} of {$a->total} rows can be imported.';
$string['import_run'] = 'Run import';
$string['import_video_ref'] = 'Video reference';
$string['import_err_name'] = 'Name is missing.';
$string['import_err_source'] = 'Invalid video source (allowed: vimeo, ignite, sharepoint, s3).';
$string['import_err_ref'] = 'Invalid video reference for source “{$a}”.';
$string['import_err_file'] = 'The file could not be read: {$a}';
$string['import_err_nocolumn'] = 'The “name” column is missing in the header row.';
$string['import_warn_existing'] = 'A section with this name already exists and will be reused.';
$string['import_warn_duplicate_file'] = 'Name appears more than once in the file and will be reused.';
$string['import_warn_noref'] = 'Video source without reference – video will be ignored.';
$string['import_warn_video_ignored'] = 'Existing section: video data is not applied.';
$string['import_done'] = 'Import complete: {$a->created} sections created, {$a->reused} reused, {$a->skipped} skipped, {$a->routescreated} routes created.';

$string['declaration_open_hint'] = 'Please confirm your completion declaration so the order counts as done.';

$string['compliance_replaceable_by'] = 'replaceable by';
$string['compliance_subst_user'] = 'user decides';
$string['compliance_subst_auto'] = 'automatic';

$string['dashboard_group_content'] = 'Routes & content';
$string['dashboard_group_assign'] = 'Operating sites & assignments';
$string['dashboard_group_reports'] = 'Reports & compliance';
$string['dashboard_group_config'] = 'Settings & master data';

$string['roundtrip_done_count'] = '{$a->done} of {$a->total} directions done';

$string['rides_lead'] = 'Record your learning rides and see which sections are still missing.';
$string['stat_total_duration'] = 'Total order duration';
$string['stat_routes_done'] = 'Routes completed';
$string['stat_pending_confirm'] = 'Awaiting confirmation';
$string['stat_pending_confirm_sub'] = 'Your rides with the trainer';
$string['duration_incomplete_short'] = 'duration incomplete';
$string['no_rideable_routes'] = 'No rides can currently be recorded for your assignments.';

$string['partner_route_hint'] = 'This route is linked to its opposite direction “{$a}”.';
$string['copy_partner_btn'] = 'Copy matrix and substitution rules to the opposite direction';
$string['copy_partner_confirm'] = 'The existing requirements and substitution rules of the opposite direction will be overwritten. Continue?';
$string['copy_partner_done'] = 'Requirements and substitution rules were copied to the opposite direction. The target duration is section-based and must be maintained there separately.';

$string['my_routes'] = 'My routes';
$string['gallery_sections_done'] = '{$a->done} of {$a->total} sections fulfilled';
$string['grid_hint'] = 'In a row, mark the sections you rode (click or drag, contiguous only). The form to record the ride then appears.';
$string['grid_required_n'] = '{$a}× required';
$string['grid_ride_n'] = 'Ride {$a}';
$string['grid_ride_extra'] = 'additional ride';
$string['grid_selection'] = 'Selection:';

$string['compliance_cell'] = 'Target {$a->soll} | Actual {$a->ist}';
$string['compliance_cell_subst'] = '{$a} substituted';
$string['compliance_cell_pending'] = '{$a} pending';
$string['compliance_subst_applied'] = 'applied by the employee';
$string['compliance_subst_notapplied'] = 'not applied by the employee';
$string['compliance_no_rules'] = 'No substitution rules.';
$string['compliance_subst_count'] = 'Number replaced';
$string['compliance_subst_rides'] = 'Credited rides (date / train number)';
$string['compliance_subst_note'] = 'Counted as substitute for {$a->method} ({$a->section})';
$string['compliance_no_substitution_used'] = 'No substitution was credited.';
$string['compliance_rides_heading'] = 'Recorded rides';
$string['compliance_recorded_on'] = 'Recorded on';
$string['compliance_decision'] = 'Decision / notes';
$string['compliance_by_signature'] = 'by signature';

$string['declaration_esign_note'] = 'The signature was given electronically by confirming the declaration in the system (user: {$a->user}, {$a->time}).';

$string['pdf_page_x_of_y'] = 'Page {$a->page} of {$a->total}';
$string['compliance_idnumber'] = 'ID no.';

$string['compliance_grid_legend'] = 'Green = confirmed ride, yellow = awaiting confirmation, ⇄ = counted as a substitute for another learning method. One ride per row; the bar spans the sections ridden.';

$string['import_data_title'] = 'Data import (CSV/ZIP)';
$string['import_data_desc'] = 'Master data, routes, requirements (number of learning rides per route and method), substitutions, operating sites and assignments can be imported from CSV – individually or as a ZIP with several files (in the order of the table). Only NEW records are created: existing ones are skipped and never overwritten. References to other records use an optional “key” or the name. There is always a preview before anything is written. For imports that depend on existing data, exports in the import format are available: “Fill in missing” gives a template with all existing references and empty cells to fill in; “Existing” shows what is already there. * = required column.';
$string['import_columns'] = 'Columns';
$string['import_downloads'] = 'Template / export';
$string['import_dl_blank'] = 'Empty template';
$string['import_dl_missing'] = 'Fill in missing';
$string['import_dl_existing'] = 'Existing';
$string['import_all_zip'] = 'All files as ZIP';
$string['import_upload'] = 'Upload file';
$string['import_file'] = 'CSV or ZIP file';
$string['import_filetype'] = 'File type';
$string['import_filetype_help'] = 'File type';
$string['import_filetype_help_help'] = '“Automatic” detects the type from the file name (e.g. 04_strecken.csv or anforderungen.csv). For ZIP files every file is detected by its name.';
$string['import_type_auto'] = 'Automatic';
$string['import_type_unknown'] = 'File type of “{$a}” not recognised – please choose the file type or rename the file.';
$string['import_file_ignored'] = 'File “{$a}” in the ZIP ignored (unknown name or too large).';
$string['import_zip_failed'] = 'The ZIP could not be read.';
$string['import_type_methoden'] = 'Learning methods';
$string['import_type_erfahrungstypen'] = 'Experience types';
$string['import_type_abschnitte'] = 'Sections (incl. video reference)';
$string['import_type_strecken'] = 'Routes (incl. outbound/return pairing)';
$string['import_type_strecken_abschnitte'] = 'Sections per route (order)';
$string['import_type_abschnitt_dauer'] = 'Target duration per section and method';
$string['import_type_anforderungen'] = 'Requirements: number of learning rides per route, experience type and method';
$string['import_type_ersetzungen'] = 'Substitution rules';
$string['import_type_einsatzstellen'] = 'Operating sites';
$string['import_type_einsatzstellen_strecken'] = 'Routes per operating site';
$string['import_type_zuweisungen'] = 'Assignments (user – route/operating site)';
$string['import_preview_note'] = 'Preview – nothing has been saved yet. Check the messages, then start the import.';
$string['import_counts_preview'] = '{$a->created} would be created, {$a->skipped} skipped, {$a->errors} errors';
$string['import_counts_done'] = '{$a->created} created, {$a->skipped} skipped, {$a->errors} errors';
$string['import_line'] = 'Line';
$string['import_message'] = 'Message';
$string['import_status_created'] = 'New';
$string['import_status_skipped'] = 'Skipped';
$string['import_status_error'] = 'Error';
$string['import_more_rows'] = '… {$a} more rows';
$string['import_again'] = 'Start another import';
$string['import_nothing_new'] = 'There is nothing new to create.';
$string['import_session_expired'] = 'The import data has expired or belongs to another session. Please upload the file again.';
$string['import_no_dryrun'] = 'The import needs a database with transactions (InnoDB) to produce the preview safely. This system does not meet that requirement.';
$string['import_missing_columns'] = 'Required column(s) missing: {$a}';
$string['import_unknown_column'] = 'Unknown column “{$a}” is ignored.';
$string['import_exists'] = 'Already exists – skipped.';
$string['import_empty_row'] = 'Nothing filled in – skipped.';
$string['import_ambiguous'] = '“{$a}” is ambiguous (several matches) – please use a key.';
$string['import_not_found'] = '{$a->what} “{$a->ref}” not found.';
$string['import_type_required'] = 'Experience type missing (there are several active types).';
$string['import_err_ril'] = 'ril100 is required for location_type “node”.';
$string['import_err_direction'] = 'Invalid direction (allowed: hin, rueck).';
$string['import_err_selfpair'] = 'A route cannot be paired with itself.';
$string['import_err_alreadypaired'] = 'Route or opposite direction is already paired.';
$string['import_route_has_sections'] = 'Route already has sections – unchanged.';
$string['import_err_duplicate_section'] = 'Section appears more than once in the route.';
$string['import_err_minutes'] = 'Minutes must be a whole number of at least 1.';
$string['import_err_count'] = 'Invalid count “{$a}” (whole number from 0).';
$string['import_requirements_summary'] = '{$a->new} values created, {$a->old} already existed';
$string['import_assign_summary'] = '{$a->new} assignment(s) created, {$a->old} already existed';
$string['import_einsatz_empty'] = 'The operating site contains no routes.';
$string['import_err_waive_reason'] = 'A reason (waive_reason) is required when waiving.';

$string['rides_to_confirm_n'] = '{$a} learning ride(s) await your confirmation';
$string['rides_to_confirm_more'] = '… show {$a} more';

$string['import_type_not_allowed'] = 'You lack the required capability for “{$a}” – file ignored.';
