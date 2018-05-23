<?php
namespace frontend\controllers;

use Yii;
use Detection\MobileDetect;

use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\helpers\Html;

use common\models\Usuarios;
use common\models\LoginForm;
use frontend\models\VerificarForm;

/**
 * Site controller
 */
class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout', 'verificar'],
                'rules' => [
                    [
                        'actions' => ['verificar'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return mixed
     */
    public function actionIndex()
    {
        $detect = new MobileDetect();

        return $this->render('index', [
            'detect' => $detect
        ]);
    }

    /**
     * Envía una invitación de un nuevo usuario a los administradores.
     * @return mixed
     */
    public function actionUnete()
    {
        $model = new Usuarios([
            'scenario' => Usuarios::ESCENARIO_UNETE
        ]);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            \Yii::$app->session->setFlash('success', 'Se ha enviado la petición al administrador correctamente. Correo suministrado: <b>' . Html::encode($model->correo) . '</b>');

            return $this->redirect(['index']);
        }

        return $this->render('unete', [
            'model' => $model,
        ]);
    }

    /**
     * Loguea un usuario
     *
     * @return mixed
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        } else {
            $model->password = '';

            return $this->render('login', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Verifica un usuario válido
     * @param  string $auth codigo de autenticación dado en el correo de verificación.
     * @return mixed
     */
    public function actionVerificar($auth)
    {
        $usuario = Usuarios::findByVerificado($auth);

        if ($usuario != null) {
            if (!$usuario->estaActivo) {
                throw new BadRequestHttpException('El usuario no está aceptado');
            }

            if (!$usuario->estaVerificado) {
                $usuario->scenario = Usuarios::ESCENARIO_VERIFICAR;

                $model = new VerificarForm();

                if ($model->load(Yii::$app->request->post()) && $model->validate()) {
                    $usuario->password = $model->password;
                    $usuario->save();

                    \Yii::$app->session->setFlash('success', 'La cuenta ha sido verificada correctamente');

                    return $this->redirect(['login']);
                }

                return $this->render('verificar', [
                    'usuario' => $usuario,
                    'model' => $model
                ]);
            }
        }

        return $this->redirect(['index']);
    }

    /**
     * Desloguea al usuario logueado actualmente.
     *
     * @return mixed
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }
}
