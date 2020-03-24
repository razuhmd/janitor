<?php
/**
* @package janitor.items
*/

//This class holds Taglist functionallity.

// define default database name constants
// base DB tables


class Taglist extends Model {   //Class name always starts with a capital letter

	/**
	* Init, set varnames, validation rules
	*/
	function __construct() {

		parent::__construct(get_class());

		$this->db = SITE_DB.".taglists"; // Conventionally we write the base table name db
		$this->db_taglist_tags = SITE_DB.".taglist_tags"; // table of references could be named anything instead of db_taglist_tags
		$this->db_tags = SITE_DB.".tags";

		$this->addToModel("name", array(
			"type" => "string",
			"label" => "Name",
			"required" => true,
			"hint_message" => "Type string",
			"error_message" => "String must be string"
		));

		$this->addToModel("handle", array(
			"type" => "string",
			"label" => "Handle",
			"required" => true,
			"hint_message" => "Type string",
			"error_message" => "String must be string"
		));
	}

	/**
	 * Get all tags
	 * 
	 * No parameter is passed
	 *
	 * @return array|false An array of at least one tag object. False on either empty array or error.
	 */
	function getAllTags(){

		$query = new Query();
		$sql = "SELECT * FROM ".$this->db_tags;
		if($query->sql($sql)) {
			return $query->results();
		}

		return false;

	}

	/**
	 * Get all tag lists
	 * 
	 * No parameter is passed
	 *
	 * @return array|false An array of at least one taglist object. False on either empty array or error.
	 */
	function getTaglists(){

		$query = new Query();
		$sql = "SELECT * FROM ".$this->db;
		if($query->sql($sql)) {
			return $query->results();
		}

		return false;

	}


	/**
	 * Get a taglist by either the taglist_id or the handle
	 * 
	 * Passing no parameter will return false
	 *
	 * @param array|false $_options
	 * * taglist_id - get a specific taglist object with the taglist_id
	 * * handle - get a specific taglist object with the handle 
	 * 
	 * @return array|false One taglist object. False on error.
	 */
	function getTaglist($_options = false) {

		// Define default values
		$taglist_id = false;
		$handle = false;

		// Search through $_options to find recognized parameters
		if($_options !== false) {
			foreach($_options as $_option => $_value) {
				switch($_option) {
					case "taglist_id"        : $taglist_id             = $_value; break;
					case "handle"            : $handle                 = $_value; break;
				}
			}
		}

		// Query database for taglist with specific id.
		if($taglist_id || $handle) {

			$query = new Query();

			if($taglist_id) {
				$sql = "SELECT * FROM ".$this->db." WHERE id = '$taglist_id'";
			}
			else {
				$sql = "SELECT * FROM ".$this->db." WHERE handle = '$handle'";
			}

			if($query->sql($sql)) {

				$taglist = $query->result(0);


				// Get tags for taglist
				$sql = "SELECT tags.id, tags.context, tags.value, taglist_tags.position FROM ".$this->db_taglist_tags.", ". $this->db_tags." WHERE taglist_tags.tag_id = tags.id AND taglist_tags.taglist_id = '".$taglist["id"]."' ORDER BY taglist_tags.position ASC";

				if($query->sql($sql)) {
					$taglist["tags"] = $query->results();
				}
				else {
					$taglist["tags"] = false;
				}
				//print_r($taglist);
				return $taglist;
			}
		}

		return false;
	}


	/**
	 * Add a tag to a taglist by the tag_id and the taglist_id
	 * 
	 * /#controller#/addTaglistTag/#taglist_id_id#/#tag_id#
	 *
	 * @param array $action
	 * 
	 * @return boolean true on success and false on error
	 */
	function addTaglistTag($action){

		$taglist_id = $action[1];
		$tag_id = $action[2];

		if($taglist_id && $tag_id){
			$query = new Query();
			$query->checkDbExistence($this->db_tags);
			$query->checkDbExistence($this->db);
			$query->checkDbExistence($this->db_taglist_tags);

			$sql = "SELECT * FROM ".$this->db_tags." WHERE id = '$tag_id'";
			if($query->sql($sql)) {
				$sql = "SELECT * FROM ".$this->db." WHERE id = '$taglist_id'";
				if($query->sql($sql)) {
					$sql = "SELECT * FROM ".$this->db_taglist_tags." WHERE taglist_id = '$taglist_id' AND tag_id = '$tag_id'";
					if(!$query->sql($sql)) {
						$sql = "INSERT INTO ".$this->db_taglist_tags." SET taglist_id = '$taglist_id', tag_id = '$tag_id'";
						if($query->sql($sql)) {
							message()->addMessage("Tag added");
							return true;
						}
					}
				}
			}
		}

		message()->addMessage("Tag could not be added due to an eror or the tag has already been added..", ["type" => "error"]);
		return false;

	}


	/**
	 * Remove a tag from a taglist by the $tag_id and the $taglist_id
	 * 
	 * /#controller#/removeTaglistTag/#taglist_id#/#tag_id#
	 *
	 * @param array $action
	 * 
	 * @return boolean true on success and false on error
	 */
	function removeTaglistTag($action){

		$taglist_id = $action[1];
		$tag_id = $action[2];

		if($taglist_id && $tag_id){
			$query = new Query();
			$query->checkDbExistence($this->db_taglist_tags);

			$sql = "SELECT * FROM ".$this->db_taglist_tags." WHERE taglist_id = '$taglist_id' AND tag_id = '$tag_id'";
			if($query->sql($sql)) {
				$sql = "DELETE FROM ".$this->db_taglist_tags." WHERE taglist_id = '$taglist_id' AND tag_id = '$tag_id'";
				if($query->sql($sql)) {
					message()->addMessage("Tag removed");
					return true;
				}
			}
		}

		message()->addMessage("Tag could not be removed due to an error or the tag had never been added..", ["type" => "error"]);
		return false;

	}


	/**
	 * Update a taglist by the taglist_id
	 * 
	 * /#controller#/updateTaglist/#taglist_id#
	 * info in $_POST
	 *
	 * @param array $action
	 * 
	 * @return boolean true on success and false on error
	 */
	function updateTaglist($action){
		// Get content of $_POST array which have been "quality-assured" by Janitor 
		$this->getPostedEntities();

		if(count($action) == 2 && $this->validateList(array("name"))) {

			$taglist_id = $action[1];
			$query = new Query();
 
			$query->checkDbExistence($this->db);
			
			// Get posted values
			$name = $this->getProperty("name", "value");
			$handle = $this->getProperty("handle", "value");
			//print_r($handle);
			$handle = superNormalize($handle);

			// Check if the taglist is already created (to avoid faulty double entries)  
			$sql = "SELECT * FROM ".$this->db." WHERE handle = $handle";
			if(!$query->sql($sql)) {
				// enter the List into the database
				$sql = "UPDATE ".$this->db." SET name ='$name', handle ='$handle' WHERE id = '$taglist_id'";
				// if successful, add message and return List id
				if($query->sql($sql)) {
					message()->addMessage("List updated");
					return true;
				}
			}
			else {
				message()->addMessage("List already exists. Try another name", array("type"=>"error"));
				return false;
			}

		}

		// something went wrong
		message()->addMessage("Could not create list due to an error.", array("type"=>"error"));
		return false;
	}


	/**
	 * Save a taglist by the name
	 * 
	 * /#controller#/saveTaglist
	 * Input information is in $_POST
	 *
	 * @param array $action
	 * 
	 * @return boolean true on success and false on error
	 */
	function saveTaglist($action){
		// Get content of $_POST array which have been "quality-assured" by Janitor 
		$this->getPostedEntities();

		if(count($action) == 1 && $this->validateList(array("name"))) {

			$query = new Query();
 
			$query->checkDbExistence($this->db);
			
			// Get posted values
			$name = $this->getProperty("name", "value");
			$handle = superNormalize($name);

			// Check if the taglist is already created (to avoid faulty double entries)  
			$sql = "SELECT * FROM ".$this->db." WHERE name = '$name'";
			if(!$query->sql($sql)) {
				// enter the List into the database
				$sql = "INSERT INTO ".$this->db." SET name ='$name', handle ='$handle'";
				
				// if successful, add message and return List id
				if($query->sql($sql)) {
					message()->addMessage("List created");
					return array("id" => $query->lastInsertId());
				}
			}
			else {
				message()->addMessage("List already exists.", array("type"=>"error"));
				return false;
			}

		}

		message()->addMessage("Could not create list.", array("type"=>"error"));
		return false;
	}


	/**
	 * Delete a taglist by the taglist_id
	 * 
	 * /#controller#/deleteTaglist/#taglist_id#
	 *
	 * @param array $action
	 * 
	 * @return boolean true on success and false on error
	 */
	function deleteTaglist($action){
		global $page;
		if(count($action) == 2) {

			$taglist_id = $action[1];

			$query = new Query();
			$fs = new FileSystem();

			// delete item + itemtype + files
			$sql = "SELECT id FROM ".$this->db." WHERE id = '$taglist_id'";
			// debug([$sql]);
			if($query->sql($sql)) {

				$sql = "DELETE FROM ".$this->db." WHERE id = '$taglist_id'";
				// debug([$sql]);
				if($query->sql($sql)) {
					
					message()->addMessage("Taglist deleted");

					// add log
					$page->addLog("ItemType->delete ($taglist_id)");

					return true;

				}
			}
		}

		message()->addMessage("Taglist could not be deleted", array("type" => "error"));
		return false;
	}


	/**
	 * Duplicate a taglist by the taglist_id
	 * 
	 * /#controller#/duplicateTaglist/#taglist_id#
	 * info in $_POST
	 *
	 * @param array $action
	 * 
	 * @return array|false One taglist object. False on error 
	 */
	function duplicateTaglist($action) {
		if(count($action) == 2) {
			$taglist_id = $action[1];

			$taglist = $this->getTaglist(array("taglist_id" => $taglist_id));
			//print_r($taglist_tags);

			if($taglist) {
				$query = new Query();
				$fs = new FileSystem();

				unset($_POST);

				$_POST["name"] = $taglist["name"]." (cloned ".date("Y-m-d H:i:s").")";
				// create root item
				$cloned_taglist = $this->saveTaglist(["saveTaglist"]);

				unset($_POST);

				// Did we succeed in creating duplicate taglist, then add the associated tag/s with the clone

				if($cloned_taglist) {

					$new_taglist_id = $cloned_taglist["id"];

					// add tags

					if($taglist["tags"]) {
						foreach($taglist["tags"] as $taglist_tag) {
							//print_r($taglist_tag);
							$this->addTaglistTag(["addTaglistTag", $new_taglist_id, $taglist_tag["id"]]);
						}
					}

					message()->resetMessages();
					message()->addMessage("Taglist duplicated");

					$taglist = $this->getTaglist(array("taglist_id" => $cloned_taglist["id"]));
					//print_r("#".$taglist."#");
					return $taglist;
				}
			}
		}
		return false;
	}


	/**
	 * Update order of the tags added to a taglist
	 * 
	 * /#controller#/updateOrder/#taglist_id#
	 * info in $_POST
	 *
	 * @param array $action
	 * 
	 * @return boolean true on success and false on eror
	 */
	function updateOrder($action) {

		$order_list = getPost("order"); //$order_list is getting the reordered tags' ids using "," seperator.
		//print_r($order_list); Could be seen in the network tab as it is handled by javascript.
		if(count($action) == 2 && $order_list) {
			$taglist_id = $action[1];

			$query = new Query();
			$order = explode(",", $order_list); //"," seperated tags' ids are explode into an array

			for($i = 0; $i < count($order); $i++) {
				$tag_id = $order[$i];
				$sql = "UPDATE ".$this->db_taglist_tags." SET position = ".($i+1)." WHERE taglist_id = ".$taglist_id." AND tag_id = ".$tag_id;
				$query->sql($sql);
			}

			message()->addMessage("Order updated");
			return true;
		}

		message()->addMessage("Order could not be updated - please refresh your browser", array("type" => "error"));
		return false;

	}

}

?>