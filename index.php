<?php

// On charge le framework Silex
require_once 'vendor/autoload.php';

// On définit des noms utiles
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Silex\Provider\DoctrineServiceProvider;
use Symfony\Component\HttpFoundation as HTTP;

// On crée l'application et on la configure en mode debug
$app = new Application();
$app['debug'] = true;


// entregister la BDD
$app->register(new Silex\Provider\DoctrineServiceProvider(),
  array('db.options' => array(
        'driver'   => 'pdo_mysql',
        'host'     => getenv('IP'),  // pas touche à ça : spécifique pour C9 !
        'user'     => substr(getenv('C9_USER'), 0, 16),  // laissez comme ça
        'password' => '',
        'dbname' => 'c9'  // mettez ici le nom de la base de données
  )));
  
//
 $app->register(new Silex\Provider\TwigServiceProvider(), 
               array('twig.path' => 'templates'));
//               
$app->register(new Silex\Provider\SessionServiceProvider());

// Nouvelle partie si la précente est finie
$app->get('/newgame', function(Application $app) {
 //Ligne 73
 $check=gameend($app);
 if ($check==81)
 { //on réinitialise les scores à 0
   $q = $app['db']->prepare("UPDATE user SET score=?");
        try {
        // Envoyer la requête
        $rows = $q->execute(array(0));
        } catch (Doctrine\DBAL\DBALException $e) {}
        //on réinitialise les cases du tab à 132
   $q = $app['db']->prepare("UPDATE tableau SET A=? ,B=? ,C=? ,D=? ,E=? ,F=? ,G=? ,H=? ,I=?");
    try {
        // Envoyer la requête
        $rows = $q->execute(array(132,132,132,132,132,132,132,132,132));
        } catch (Doctrine\DBAL\DBALException $e) {}
 }
  return $app->redirect('/');});
  
  
// Classement des joueurs
$app->get('/classement', function(Application $app) {
 $q = $app['db']->executeQuery('SELECT * FROM user ORDER BY score DESC');
  $results = $q->fetchAll();
  return $app['twig']->render('classement.twig', array('row'=>$results));});

// Recuperation des utilisateurs connectes
$app->get('/connected', function(Application $app) {
 $q = $app['db']->executeQuery('SELECT * FROM user where connecte=1');
  $results = $q->fetchAll();
  return $app['twig']->render('connected.twig', array('row'=>$results));});

// Check fin de partie : si toutes les cases sont au niveau 0.
function gameend(Application $app){
 $data= sendtab($app);
 $check=0;
 for ($i=0;$i<9;$i++)
  {
   for ($j=0;$j<9;$j++)
   {
    if ($data[$i][$j]==0) {
     $check=$check+1;
    }
    
   }
  }
  return $check;
 };

//Recupere les plateau dans la BD, et l'envoie sous forme de tableau à l'application JS
function sendtab(Application $app){
 
$L0 = $app['db']->fetchAssoc("SELECT * FROM tableau WHERE id = ?", array("0"));

$L1 = $app['db']->fetchAssoc("SELECT * FROM tableau WHERE id = ?",array("1"));
 
$L2 = $app['db']->fetchAssoc("SELECT * FROM tableau WHERE id = ?",array("2"));

$L3 = $app['db']->fetchAssoc("SELECT * FROM tableau WHERE id = ?",array("3"));  
 
$L4 = $app['db']->fetchAssoc("SELECT * FROM tableau WHERE id = ?", array("4"));
 
$L5 = $app['db']->fetchAssoc("SELECT * FROM tableau WHERE id = ?",array("5"));
 
$L6 = $app['db']->fetchAssoc("SELECT * FROM tableau WHERE id = ?",array("6"));
 
$L7 = $app['db']->fetchAssoc("SELECT * FROM tableau WHERE id = ?",array("7"));
 
$L8 = $app['db']->fetchAssoc("SELECT * FROM tableau WHERE id = ?",array("8"));
       
    $data = array(array($L0['A'],$L0['B'],$L0['C'],$L0['D'],$L0['E'],$L0['F'],$L0['G'],$L0['H'],$L0['I']),array($L1['A'],$L1['B'],$L1['C'],$L1['D'],$L1['E'],$L1['F'],$L1['G'],$L1['H'],$L1['I']),array($L2['A'],$L2['B'],$L2['C'],$L2['D'],$L2['E'],$L2['F'],$L2['G'],$L2['H'],$L2['I']),array($L3['A'],$L3['B'],$L3['C'],$L3['D'],$L3['E'],$L3['F'],$L3['G'],$L3['H'],$L3['I']),array($L4['A'],$L4['B'],$L4['C'],$L4['D'],$L4['E'],$L4['F'],$L4['G'],$L4['H'],$L4['I']),array($L5['A'],$L5['B'],$L5['C'],$L5['D'],$L5['E'],$L5['F'],$L5['G'],$L5['H'],$L5['I']),array($L6['A'],$L6['B'],$L6['C'],$L6['D'],$L6['E'],$L6['F'],$L6['G'],$L6['H'],$L6['I']),array($L7['A'],$L7['B'],$L7['C'],$L7['D'],$L7['E'],$L7['F'],$L7['G'],$L7['H'],$L7['I']),array($L8['A'],$L8['B'],$L8['C'],$L8['D'],$L8['E'],$L8['F'],$L8['G'],$L8['H'],$L8['I']));
    return $data;
}

// 
$app->match('/api/tab', function(Application $app,Request $req) {
 if($req->getMethod()=="GET")
    {$data=sendtab($app);
      return $app->json($data);
    }

// Recuperes les coordonnes du "clic" du joueur sous la forme 'ligne''colonne'
if ($req->getMethod()=="POST")
 {
  $tab = $req->request->get("tab");
  $ligne = $tab[0] ;
  $colonne = $tab[1] ;
  $pseudo='';
  
  
  
  $data=sendtab($app,$req);
  $max=$data[0][0];
 
  
  for ($i=0;$i<9;$i++)
  {
   for ($j=0;$j<9;$j++)
   {
    //Le max du plateau correspond au niveau courant
    if ($max<$data[$i][$j]) {
     $max=$data[$i][$j];
    }
    
   }
  }
  $level=$max-1;
  //Verifie que la case cliqué appartient au niveau courant.
   $verif = $app['db']->fetchAssoc("SELECT * FROM tableau WHERE id = ?",array($ligne));
  
  //
  if ($colonne==0){ $colonne="A";}
  else if ($colonne==1){ $colonne="B";}
  else if ($colonne==2){ $colonne="C";}
  else if ($colonne==3){ $colonne="D";}
  else if ($colonne==4){ $colonne="E";}
  else if ($colonne==5){ $colonne="F";}
  else if ($colonne==6){ $colonne="G";}
  else if ($colonne==7){ $colonne="H";}
  else if ($colonne==8){ $colonne="I";}
  
  
  
  if($max - $verif[$colonne]<1 && $max!=0)
  {
    $q = $app['db']->prepare("UPDATE tableau SET $colonne=? WHERE id = ?");
        try {
        // Envoyer la requête
        $rows = $q->execute(array($level,$ligne));
        } catch (Doctrine\DBAL\DBALException $e) {
        // En cas d'erreur, afficher les informations dans le browser
        // et terminer (Beurk ! Pour debug uniquement)
        
        }
    for ($a=2;$a<20;$a++)
   {
   $pseudo.=$tab[$a];}
   $a=0;
    $q = $app['db']->fetchAssoc("SELECT * from user where pseudo=?",array($pseudo));
   //Incremente le score du joueur
    $score=$q['score'];
   
     $q = $app['db']->prepare("UPDATE user SET score= ? WHERE pseudo=?");
        try {
        // Envoyer la requête
        $rows = $q->execute(array($score+1,$pseudo));
        } catch (Doctrine\DBAL\DBALException $e) {
        // En cas d'erreur, afficher les informations dans le browser
        // et terminer (Beurk ! Pour debug uniquement)
        
        }
  }
  $data=sendtab($app,$req);
   return $app->json($data);
 }
   
 
 
});
//gestion de la deconnexion
$app->POST('/deconnexion', function(Application $app,Request $req) {
      $email = $req->request->get("pseudo");
    $q = $app['db']->prepare('UPDATE user SET connecte = ? WHERE pseudo = ?');
        try {
        // Envoyer la requête
        $rows = $q->execute(array(0,$email));
        return $app->redirect('/');
        
        } catch (Doctrine\DBAL\DBALException $e) {
        // En cas d'erreur, afficher les informations dans le browser
        // et terminer (Beurk! Pour debug uniquement)
        
        }
    
 
});

$app->match('/', function(Application $app,Request $req) {
   if($req->getMethod()=="POST")
    {
     $email = $req->request->get("email");
     $password = $req->request->get("password");
     $rememberme = $req->request->get("rememberme");
     $message='';
     
     if ($email!=''&$password!='')
     {
      $hashpass=md5($password);
       // Préparer la requête
      $q = $app['db']->fetchAssoc("SELECT * FROM user WHERE email = ? AND password = ?",
                       array($email,$hashpass));
      $pseudo = $q['pseudo'];
        
        
        $valide=0;
       
         if($q['email']==$email & $hashpass==$q['password'])
         {
          $valide=1;
         }
        
        if($valide==1)
        {
          $_SESSION["email"]		   = $email;
			       
		       	if(!empty($rememberme)) {
		      		setcookie ("member_login",md5($email),time()+ ( 365 * 24 * 60 * 60));
				      setcookie ("member_password",md5($password),time()+ (10 * 365 * 24 * 60 * 60));
				      setcookie ("member_IP",md5($password),time()+ (10 * 365 * 24 * 60 * 60));
			} else {
			  	if(isset($_COOKIE["member_login"])) {
					setcookie ("member_login","");
				}
				
			}
			  // Préparer la requête
        $q = $app['db']->prepare('UPDATE user SET connecte = ? WHERE email = ?');
        try {
        // Envoyer la requête
        $rows = $q->execute(array(1,$email));
        } catch (Doctrine\DBAL\DBALException $e) {
        // En cas d'erreur, afficher les informations dans le browser
        // et terminer (Beurk ! Pour debug uniquement)
        
        }
	
        return $app['twig']->render('curiosity.twig', array('email'=>$pseudo));
        
        }
        
        
      
        else if ($valide==0){
          $message='Vos identifiants sont incorrectes';
          return $app['twig']->render('login.twig', array('message'=>$message));}
          
          
         }
 
        $message='Veuillez entrer vos identifiants';
        return $app['twig']->render('login.twig', array('message'=>$message));}
      
   
     return $app['twig']->render('login.twig', array('message'=>$message));});

$app->match('/signin',function(Application $app,Request $req){
 $message='';
    if($req->getMethod()=="POST")
    {
     $fname = $req->request->get("fname");
     $lname = $req->request->get("lname");
     $email = $req->request->get("email");
     $password = $req->request->get("password");
     $pseudo = $req->request->get("pseudo");

     
      if ($fname!=''& $lname!=''& $email!=''& $password!=''& $pseudo!='')
      {
        // Préparer la requête
        $q = $app['db']->prepare('INSERT INTO user VALUES (?, ?, ?, ?, ?, ?, ?)');
        try {
        // Envoyer la requête
        $rows = $q->execute(array($email, md5($password), $fname, $lname,0,$pseudo,0));
        } catch (Doctrine\DBAL\DBALException $e) {
        // En cas d'erreur, afficher les informations dans le browser
        // et terminer (Beurk ! Pour debug uniquement)
        
        if($q->errorCode()==23000){
        $message='Le pseudo choisi est déjà utilisé';}
        return $app['twig']->render('signin.twig', array('message' => $message));
        }
        return $app->redirect('/');
      }
      else {
       return $app['twig']->render('signin.twig', array('message' => $message));
      }
    }
    else{
     
     return $app['twig']->render('signin.twig', array('message' => $message));}
  });

$app->run();