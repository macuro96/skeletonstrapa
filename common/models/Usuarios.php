<?php

namespace common\models;

use Yii;
use \yii\web\IdentityInterface;

/**
 * This is the model class for table "usuarios".
 *
 * @property int $id
 * @property string $nombre
 * @property string $password
 * @property string $correo
 * @property int $nacionalidad_id
 * @property bool $verificado
 *
 * @property Jugadores $jugadores
 * @property Nacionalidades $nacionalidad
 */
class Usuarios extends \yii\db\ActiveRecord implements IdentityInterface
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'usuarios';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['nombre', 'password', 'correo', 'nacionalidad_id'], 'required'],
            [['nacionalidad_id'], 'default', 'value' => null],
            [['nacionalidad_id'], 'integer'],
            [['activo'], 'boolean'],
            [['nombre', 'password', 'correo', 'access_token', 'auth_key', 'verificado'], 'string', 'max' => 255],
            [['correo'], 'unique'],
            [['nacionalidad_id'], 'exist', 'skipOnError' => true, 'targetClass' => Nacionalidades::className(), 'targetAttribute' => ['nacionalidad_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'nombre' => 'Nombre',
            'password' => 'Contraseña',
            'correo' => 'Correo',
            'nacionalidad_id' => 'Nacionalidad',
            'verificado' => 'Verificado',
        ];
    }

    public function getEstaActivo()
    {
        return $this->activo === true;
    }

    public function getEstaVerificado()
    {
        return $this->verificado === null;
    }

    public static function findLoginQuery()
    {
        return static::find()
                     ->where(['activo' => true])
                     ->andWhere('verificado is null');
    }

    public static function findByNombre($nombre)
    {
        return static::findOne(['nombre' => $nombre]);
    }

    /**
      * Finds an identity by the given ID.
      *
      * @param string|int $id the ID to be looked for
      * @return IdentityInterface|null the identity object that matches the given ID.
      */
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id]);
    }

     /**
      * Finds an identity by the given token.
      *
      * @param string $token the token to be looked for
      * @param string|null $type
      * @return IdentityInterface|null the identity object that matches the given token.
      */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['access_token' => $token]);
    }

     /**
      * @return int|string current user ID
      */
    public function getId()
    {
        return $this->id;
    }

     /**
      * @return string current user auth key
      */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

     /**
      * @param string $authKey
      * @return bool if auth key is valid for current user
      */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    public function validatePassword($password)
    {
        return \Yii::$app->security->validatePassword($password, $this->password);
    }

    public function getEsAdministrador()
    {
        return $this->getRoles()->where(['nombre' => 'administrador'])->one() !== null;
    }

    /**
    * @return \yii\db\ActiveQuery
    */
    public function getJugadores()
    {
        return $this->hasOne(Jugadores::className(), ['usuario_id' => 'id'])->inverseOf('usuario');
    }

    /**
    * @return \yii\db\ActiveQuery
    */
    public function getNacionalidad()
    {
        return $this->hasOne(Nacionalidades::className(), ['id' => 'nacionalidad_id'])->inverseOf('usuarios');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUsuariosRoles()
    {
        return $this->hasMany(UsuariosRoles::className(), ['usuario_id' => 'id'])->inverseOf('usuario');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRoles()
    {
        return $this->hasMany(Roles::className(), ['id' => 'rol_id'])->viaTable('usuarios_roles', ['usuario_id' => 'id']);
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($this->isNewRecord) {
                $this->auth_key = \Yii::$app->security->generateRandomString();
            }
            return true;
        }
        return false;
    }
}