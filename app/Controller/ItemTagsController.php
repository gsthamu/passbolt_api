<?php
/**
 * ItemsTags Controller
 *
 * @copyright   Copyright 2012, Passbolt.com
 * @license     http://www.passbolt.com/license
 * @package     app.Controller.GroupsController
 * @since       version 2.13.6
 */
class ItemTagsController extends AppController {

	public $helpers = array('PassboltAuth');

	/**
	 * Index
	 *
	 * @access public
	 */
	public function viewForeignItemTags($foreignModel = null, $foreignId = null) {
		$foreignModelName = Inflector::camelize($foreignModel);

		// check the HTTP request method
		if (!$this->request->is('get')) {
			$this->Message->error(__('Invalid request method, should be GET'));
			return;
		}

		// check if the target foreign model is commentable
		if(!$this->ItemTag->isValidForeignModel($foreignModelName)) {
			$this->Message->error(__('The model %s is not taggable', $foreignModelName));
			return;
		}

		// no instance id given
		if(is_null($foreignId)) {
			$this->Message->error(__('The %s id is missing', $foreignModelName));
			return;
		}

		// the instance id is invalid
		if (!Common::isUuid($foreignId)) {
			$this->Message->error(__('The %s id is invalid', $foreignModelName));
			return;
		}

		// the foreign instance does not exist
		$instance = $this->ItemTag->$foreignModelName->findById($foreignId);
		if (!$instance) {
			$this->Message->error(__('The %s does not exist', $foreignModelName), array('code' => 404));
			return;
		}

		// check if user is authorized.
		// if the permissionable behavior has been applied to the foreign model.
		// the permissionable after find executed on the previous operation findById should drop
		// any record the user is not authorized to access. This test should always be true.
		if (!$this->ItemTag->$foreignModelName->isAuthorized($foreignId, PermissionType::READ)) {
			$this->Message->error(__('You are not authorized to access this %s', $foreignModelName), array('code' => 403));
			return;
		}

		// find the comments
		$findData = array(
			'ItemTag' => array(
				'foreign_id' => $foreignId,
				'foreign_model' => $foreignModelName
			)
		);
		$findOptions = $this->ItemTag->getFindOptions('ItemTag.viewByForeignModel', User::get('Role.name'), $findData);

		// Retrieve the just updated item tags.
		$itemTags = $this->ItemTag->find('all', $findOptions);
		$this->set('data', $itemTags);
		$this->Message->success();
	}

	public function updateBulk($foreignModelName = null, $foreignId = null) {
		$datasource = ConnectionManager::getDataSource('default');
		$datasource->begin();

		$foreignModelName = Inflector::camelize($foreignModelName);
		$postData = $this->request->data;

		// check the HTTP request method
		if (!$this->request->is('post')) {
			$datasource->rollback();
			return $this->Message->error(__('Invalid request method, should be POST'));
		}

		// check if the target foreign model is commentable
		if(!$this->ItemTag->isValidForeignModel($foreignModelName)) {
			$datasource->rollback();
			return $this->Message->error(__('The model %s is not taggable', $foreignModelName));
		}

		// no instance id given
		if(is_null($foreignId)) {
			$datasource->rollback();
			return $this->Message->error(__('The %s id is missing', $foreignModelName));
		}

		// the instance id is invalid
		if (!Common::isUuid($foreignId)) {
			$datasource->rollback();
			return $this->Message->error(__('The %s id is invalid', $foreignModelName));
		}

		// the foreign instance does not exist
		$instance = $this->ItemTag->$foreignModelName->findById($foreignId);
		if (!$instance) {
			$datasource->rollback();
			return $this->Message->error(__('The %s does not exist', $foreignModelName), array('code' => 404));
		}

		// check if user is authorized.
		// if the permissionable behavior has been applied to the foreign model.
		// the permissionable after find executed on the previous operation findById should drop
		// any record the user is not authorized to access. This test should always be true.
		if (!$this->ItemTag->$foreignModelName->isAuthorized($foreignId, PermissionType::UPDATE)) {
			$datasource->rollback();
			return $this->Message->error(__('You are not authorized to update item tags of this %s', $foreignModelName), array('code' => 403));
		}

		$this->ItemTag->deleteAll(array(
				'ItemTag.foreign_model' => $foreignModelName,
				'ItemTag.foreign_id' => $foreignId
			),
			false
		);

		$tagList = $postData['ItemTag']['tag_list'];
		$tagList = trim($tagList, " ,");

		// If new tags have been given.
		if (!empty($tagList)) {
			$tags = explode(',', $tagList);
			foreach($tags as $tagName) {
				$tagName = trim($tagName);
				$tag = $this->ItemTag->Tag->findByName($tagName);

				// If the given tag doesn't exist, create it.
				if(!$tag) {
					$t = array(
						'name' => $tagName
					);
					$this->ItemTag->Tag->create();
					$this->ItemTag->Tag->set($t);
					if(!$this->ItemTag->Tag->validates()) {
						$datasource->rollback();
						return $this->Message->error(__('The tag named %s is not valid', $tagName));
					}
					if(!($tag = $this->ItemTag->Tag->save($t))) {
						$datasource->rollback();
						return $this->Message->error(__('There was a problem while saving tag %s', $tagName));
					}
				}

				// Create the new Item Tag.
				$itemTag = array(
					'foreign_model' => $foreignModelName,
					'foreign_id'    => $foreignId,
					'tag_id'        => $tag['Tag']['id']
				);
				$this->ItemTag->create();
				$this->ItemTag->set($itemTag);
				if(!$this->ItemTag->validates()) {
					$datasource->rollback();
					return $this->Message->error(__('The ItemTag is not valid', $tagName));
				}
				if(!$this->ItemTag->save($itemTag)) {
					$datasource->rollback();
					return $this->Message->error(__('There was a problem while saving ItemTag', $tagName));
				}
			}
		}

		$datasource->commit();

		// find the tags
		$findData = array(
			'ItemTag' => array(
				'foreign_id' => $foreignId,
				'foreign_model' => $foreignModelName
			)
		);
		$findOptions = $this->ItemTag->getFindOptions('ItemTag.viewByForeignModel', User::get('Role.name'), $findData);
		$itemTags = $this->ItemTag->find('all', $findOptions);
		$this->set('data', $itemTags);
		$this->Message->success(__('The items tags have been updated with success'));
	}

	/**
	 * Add a tag to a target taggable model instance
	 * @param string foreignModelName The target foreign model
	 * @param uuid foreignId The uuid of the target instance to create tags for
	 */
	public function addForeignItemTag($foreignModelName = null, $foreignId = null) {
		$datasource = ConnectionManager::getDataSource('default');
		$datasource->begin();

		$foreignModelName = Inflector::camelize($foreignModelName);
		$postData = $this->request->data;

		// check the HTTP request method
		if (!$this->request->is('post')) {
			$datasource->rollback();
			return $this->Message->error(__('Invalid request method, should be POST'));
		}

		// check if the target foreign model is commentable
		if(!$this->ItemTag->isValidForeignModel($foreignModelName)) {
			$datasource->rollback();
			return $this->Message->error(__('The model %s is not taggable', $foreignModelName));
		}

		// no instance id given
		if(is_null($foreignId)) {
			$datasource->rollback();
			return $this->Message->error(__('The %s id is missing', $foreignModelName));
		}

		// the instance id is invalid
		if (!Common::isUuid($foreignId)) {
			$datasource->rollback();
			return $this->Message->error(__('The %s id is invalid', $foreignModelName));
		}

		// the foreign instance does not exist
		$instance = $this->ItemTag->$foreignModelName->findById($foreignId);
		if (!$instance) {
			$datasource->rollback();
			return $this->Message->error(__('The %s does not exist', $foreignModelName), array('code' => 404));
		}

		// check if user is authorized.
		// if the permissionable behavior has been applied to the foreign model.
		// the permissionable after find executed on the previous operation findById should drop
		// any record the user is not authorized to access. This test should always be true.
		if (!$this->ItemTag->$foreignModelName->isAuthorized($foreignId, PermissionType::READ)) {
			$datasource->rollback();
			return $this->Message->error(__('You are not authorized to access this %s', $foreignModelName), array('code' => 403));
		}

		// check if data was provided
		if (!isset($postData['ItemTag']) && !isset($postData['Tag'])) {
			$datasource->rollback();
			return $this->Message->error(__('No data were provided'));
		}
		// add data to the posted data
		$postData['ItemTag']['foreign_model'] = $foreignModelName;
		$postData['ItemTag']['foreign_id'] = $foreignId;

		// a tag name has been given.
		if (isset($postData['Tag']['name'])) {
			$tagName = $postData['Tag']['name'];
			$tag = $this->ItemTag->Tag->findByName($tagName);

			// if the tag doesn't exist, create it.
			if (!$tag) {
				$this->ItemTag->Tag->create();
				$this->ItemTag->Tag->set($postData);
				// check if the data is valid.
				if(!$this->ItemTag->Tag->validates()){
					$datasource->rollback();
					return $this->Message->error($this->ItemTag->Tag->validationErrors);
				}

				$fields = $this->ItemTag->Tag->getFindFields('Tag.add', User::get('Role.name'));
				$this->ItemTag->Tag->create();
				$rs = $this->ItemTag->Tag->save($postData, true, $fields['fields']);
			}

			// add the tag id to the item tag save request.
			$postData['ItemTag']['tag_id'] = $this->ItemTag->Tag->id;
		}
		// a tag id has been given.
		else if (isset($postData['ItemTag']['tag_id'])) {
			// check that it's a valid tag.
			if (!$this->ItemTag->Tag->exists($postData['ItemTag']['tag_id'])) {
				$datasource->rollback();
				return $this->Message->error(__('The Tag id is invalid'));
			}
		}

		$this->ItemTag->set($postData);
		// check if the data is valid
		if(!$this->ItemTag->validates()){
			$datasource->rollback();
			return $this->Message->error($this->ItemTag->validationErrors);
		}

		// Save the item tag.
		$fields = $this->ItemTag->getFindFields('ItemTag.add', User::get('Role.name'));
		$this->ItemTag->save($postData, true, $fields['fields']);
		$datasource->commit();

		// return the just inserted item tag.
		$findData = array('ItemTag' => array('id' => $this->ItemTag->id));
		$findOptions = $this->ItemTag->getFindOptions('ItemTag.view', User::get('Role.name'), $findData);
		$this->set('data', $this->ItemTag->find('first', $findOptions));

		$this->Message->success(__('The tag was sucessfully added'));
	}


	/**
	 * Delete an ItemTag.
	 * @param uuid id The uuid of the item_tag to delete
	 */
	public function delete($id = null) {
		// check the HTTP request method
		if (!$this->request->is('delete')) {
			$this->Message->error(__('Invalid request method, should be DELETE'));
			return;
		}

		// no instance id given
		if(is_null($id)) {
			$this->Message->error(__('The item tag id is missing'));
			return;
		}

		// the instance id is invalid
		if (!Common::isUuid($id)) {
			$this->Message->error(__('The item tag id is invalid'));
			return;
		}

		// the foreign instance does not exist
		$instance = $this->ItemTag->findById($id);
		if (!$instance) {
			$this->Message->error(__('The item tag does not exist'), array('code' => 404));
			return;
		}

		// check if user is authorized.
		// if the permissionable behavior has been applied to the foreign model.
		// the permissionable after find executed on the previous operation findById should drop
		// any record the user is not authorized to access. This test should always be true.
		$foreignModelName = $instance['ItemTag']['foreign_model'];
		$foreignId = $instance['ItemTag']['foreign_id'];
		if (!$this->ItemTag->$foreignModelName->isAuthorized($foreignId, PermissionType::UPDATE)) {
			$this->Message->error(__('You are not authorized to delete item tags of this %s', $foreignModelName), array('code' => 403));
			return;
		}

		// Delete the target itemTag
		$this->ItemTag->delete($id, true);
		$this->Message->success(__('The ItemTag was sucessfully deleted'));
	}

}