<?php
/**
 * Passbolt ~ Open source password manager for teams
 * Copyright (c) Passbolt SARL (https://www.passbolt.com)
 *
 * Licensed under GNU Affero General Public License version 3 of the or any later version.
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Passbolt SARL (https://www.passbolt.com)
 * @license       https://opensource.org/licenses/AGPL-3.0 AGPL License
 * @link          https://www.passbolt.com Passbolt(tm)
 * @since         2.0.0
 */
namespace App\Model\Table;

use App\Error\Exception\ValidationRuleException;
use App\Utility\Gpg;
use Cake\I18n\FrozenTime;
use Cake\I18n\Date;
use Cake\I18n\Time;
use DateTimeInterface;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validation;
use Cake\Validation\Validator;
use JsonSchema\Exception\ValidationException;
use Cake\Core\Exception\Exception;

/**
 * Model to store and validate OpenPGP public keys
 *
 * @property \App\Model\Table\UsersTable|\Cake\ORM\Association\BelongsTo $Users
 *
 * @method \App\Model\Entity\Gpgkey get($primaryKey, $options = [])
 * @method \App\Model\Entity\Gpgkey newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Gpgkey[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Gpgkey|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Gpgkey patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Gpgkey[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Gpgkey findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class GpgkeysTable extends Table
{

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->setTable('gpgkeys');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER'
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator)
    {
        $validator
            ->uuid('id')
            ->allowEmpty('id', 'create');

        $validator
            ->scalar('armored_key')
            ->requirePresence('armored_key', 'create')
            ->notEmpty('armored_key')
            ->add('armored_key', ['custom' => [
                'rule' => [$this, 'isParsableArmoredPublicKey'],
                'message' => __('The key should be a valid OpenPGPG ASCII armored key.')
            ]]);

        $validator
            ->integer('bits', 'The key bits should be an integer.')
            ->requirePresence('bits', 'create')
            ->notEmpty('bits');

        $validator
            ->utf8('uid')
            ->allowEmpty('uid');

        $validator
            ->scalar('key_id')
            ->requirePresence('key_id', 'create')
            ->notEmpty('key_id')
            ->add('key_id', ['custom' => [
                'rule' => [$this, 'isValidKeyId'],
                'message' => __('The key id should be a string of 8 hexadecimal characters.')
            ]]);

        $validator
            ->scalar('fingerprint')
            ->requirePresence('fingerprint', 'create')
            ->notEmpty('fingerprint')
            ->add('fingerprint', ['custom' => [
                'rule' => [$this, 'isValidFingerprint'],
                'message' => __('The key id should be a string of 40 hexadecimal characters.')
            ]]);

        $validator
            ->scalar('type')
            ->requirePresence('type', 'create')
            ->notEmpty('type')
            ->add('type', ['custom' => [
                'rule' => [$this, 'isValidKeyType'],
                'message' => __('The key type should be one of the following: RSA, DSA, ECC, ELGAMAL, ECDSA, DH.')
            ]]);

        $validator
            ->dateTime('expires')
            ->allowEmpty('expires')
            ->add('expires', ['custom' => [
                'rule' => [$this, 'isInFuture'],
                'message' => __('The key should not already be expired.')
            ]]);

        $validator
            ->dateTime('key_created')
            ->requirePresence('key_created', 'create')
            ->notEmpty('key_created')
            ->add('key_created', ['custom' => [
                'rule' => [$this, 'isInFuturePast'],
                'message' => __('The key creation date should be set in the past.')
            ]]);

        $validator
            ->boolean('deleted')
            ->requirePresence('deleted', 'create')
            ->notEmpty('deleted');

        $validator
            ->uuid('user_id', __('The user id should be a valid uuid.'))
            ->requirePresence('user_id', 'create')
            ->notEmpty('user_id');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules)
    {
        $rules->add($rules->existsIn(['user_id'], 'Users'));
        $rules->add($rules->isUnique(['fingerprint']));

        return $rules;
    }

    /**
     * Custom validation rule to validate fingerprint
     *
     * @param string $value fingerprint
     * @param array $context not in use
     * @return bool
     */
    public function isValidFingerprint($value, array $context = null)
    {
        return (preg_match('/^[A-F0-9]{40}$/', $value) === 1);
    }

    /**
     * Custom validation rule to validate key id
     *
     * @param string $value fingerprint
     * @param array $context not in use
     * @return bool
     */
    public function isValidKeyId($value, array $context = null)
    {
        return (preg_match('/^[A-F0-9]{8}$/', $value) === 1);
    }

    /**
     * Custom validation rule to validate key id
     *
     * @param string $value fingerprint
     * @param array $context not in use
     * @return bool
     */
    public function isParsableArmoredPublicKey($value, array $context = null)
    {
        if (!is_scalar($value) || !is_string($value)) {
            return false;
        }
        $gpg = new Gpg();

        return $gpg->isParsableArmoredPublicKey($value);
    }

    /**
     * Check if a key date is set in the past... tomorrow!
     *
     * In a ideal world we should check if a key date is set in the past from 'now'
     * where now is the time of reference of the server. But in practice we
     * allow a next day margin because users had the issue of having keys generated
     * by systems that were ahead of server time. Refs. PASSBOLT-1505.
     *
     * @param Date $value Cake Datetime
     * @param array $context not in use
     * @return bool
     */
    public function isInFuturePast($value, array $context = null)
    {
        if (empty($value) || !($value instanceof DateTimeInterface)) {
            return false;
        }
        $nowWithMargin = Time::now()->modify('+12 hours');

        return $value->lt($nowWithMargin);
    }

    /**
     * Check if a key date is set in the future
     * Used to check key expiry date
     *
     * @param Date $value Cake Datetime
     * @param array $context not in use
     * @return bool
     */
    public function isInFuture($value, array $context = null)
    {
        if (empty($value) || !($value instanceof DateTimeInterface)) {
            return false;
        }

        return $value->gt(FrozenTime::now());
    }

    /**
     * Custom validation rule to validate key type
     *
     * @param string $value fingerprint
     * @param array $context not in use
     * @return bool
     */
    public function isValidKeyType($value, array $context = null)
    {
        if (empty($value) || !is_scalar($value)) {
            return false;
        }
        foreach (\OpenPGP_PublicKeyPacket::$algorithms as $i => $algorithm) {
            if ($value === $algorithm) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for valid email inside GPG key UID
     *
     * @param string $value gpg key uid
     * @param array $context not in use
     * @return bool
     */
    public function uidContainValidEmail($value, array $context = null)
    {
        preg_match('/<(\S+@\S+)>$/', $value, $matches);
        if (isset($matches[1])) {
            return Validation::email($matches[1]);
        }

        return false;
    }

    /**
     * Build the query that fetches data for user index
     *
     * @param Query $query a query instance
     * @param array $options options
     * @throws Exception if no role is specified
     * @return Query
     */
    public function findIndex(Query $query, array $options)
    {
        $query->where(['deleted' => false]);
        if (isset($options['filter']['modified-after'])) {
            $modified = date('Y-m-d H:i:s', $options['filter']['modified-after']);
            $query->where(['modified >' => $modified]);
        }

        return $query;
    }

    /**
     * Find view
     *
     * @param Query $query a query instance
     * @param array $options options
     * @throws Exception if no id is specified
     * @return Query
     */
    public function findView(Query $query, array $options)
    {
        // Options must contain an id
        if (!isset($options['id'])) {
            throw new Exception(__('Gpgkey table findView should have an id set in options.'));
        }
        // Same rule than index apply
        // with a specific id requested
        $query = $this->findIndex($query, $options);
        $query->where(['id' => $options['id']]);

        return $query;
    }

    /**
     * Get a gpg key for matching fingerprint and user id
     *
     * @param $fingerprint
     * @param $userId
     * @return array|\Cake\Datasource\EntityInterface|null
     */
    public function getByFingerPrintAndUserId($fingerprint, $userId)
    {
        return $this->find()
            ->where([
                'user_id' => $userId,
                'fingerprint' => strtoupper($fingerprint)
            ])
            ->first();
    }

    /**
     * Build a Gpgkey entity from the armored key
     *
     * @param string $armoredKey ascii armored key
     * @param string $userId uuid of the user using the key
     * @throws ValidationRuleException if the key info can not be parsed
     * @return object Gpgkey entity
     */
    public function buildEntityFromArmoredKey($armoredKey, $userId)
    {
        try {
            $gpg = new Gpg();
            $info = $gpg->getPublicKeyInfo($armoredKey);
        } catch (Exception $e) {
            throw new ValidationException(__('Could not create Gpgkey from armored key.'));
        }

        $data = [
            'user_id' => $userId,
            'fingerprint' => $info['fingerprint'],
            'bits' => $info['bits'],
            'type' => $info['type'],
            'key_id' => $info['key_id'],
            'uid' => $info['uid'],
            'armored_key' => $armoredKey,
            'deleted' => false,
            'key_created' => new FrozenTime($info['key_created']),
            'expires' => null
        ];
        if (!empty($info['expires'])) {
            $data['expires'] = new FrozenTime($info['expires']);
        }
        $gpgkey = $this->newEntity($data, ['accessibleFields' => ['*' => true]]);

        return $gpgkey;
    }
}