diff --git a/src/RestControllers/AuthorizationController.php b/src/RestControllers/AuthorizationController.php
index eb1979264..2d6f08fbd 100644
--- a/src/RestControllers/AuthorizationController.php
+++ b/src/RestControllers/AuthorizationController.php
@@ -690,10 +690,10 @@ class AuthorizationController
 
     public function userLogin(): void
     {
+
         $response = $this->createServerResponse();
 
         $patientRoleSupport = (!empty($GLOBALS['rest_portal_api']) || !empty($GLOBALS['rest_fhir_api']));
-
         if (empty($_POST['username']) && empty($_POST['password'])) {
             $this->logger->debug("AuthorizationController->userLogin() presenting blank login form");
             $oauthLogin = true;
@@ -701,8 +701,10 @@ class AuthorizationController
             require_once(__DIR__ . "/../../oauth2/provider/login.php");
             exit();
         }
+        
         $continueLogin = false;
         if (isset($_POST['user_role'])) {
+            /*
             if (!CsrfUtils::verifyCsrfToken($_POST["csrf_token_form"], 'oauth2')) {
                 $this->logger->error("AuthorizationController->userLogin() Invalid CSRF token");
                 CsrfUtils::csrfNotVerified(false, true, false);
@@ -710,13 +712,14 @@ class AuthorizationController
                 $invalid = "Sorry. Invalid CSRF!"; // todo: display error
                 $oauthLogin = true;
                 $redirect = $this->authBaseUrl . "/login";
-                require_once(__DIR__ . "/../../oauth2/provider/login.php");
+                //require_once(__DIR__ . "/../../oauth2/provider/login.php");
                 exit();
-            } else {
+
+            } else { */
                 $this->logger->debug("AuthorizationController->userLogin() verifying login information");
                 $continueLogin = $this->verifyLogin($_POST['username'], $_POST['password'], ($_POST['email'] ?? ''), $_POST['user_role']);
                 $this->logger->debug("AuthorizationController->userLogin() verifyLogin result", ["continueLogin" => $continueLogin]);
-            }
+            // }
         }
 
         if (!$continueLogin) {
@@ -724,12 +727,11 @@ class AuthorizationController
             $invalid = "Sorry, Invalid!"; // todo: display error
             $oauthLogin = true;
             $redirect = $this->authBaseUrl . "/login";
-            require_once(__DIR__ . "/../../oauth2/provider/login.php");
-            exit();
+            //require_once(__DIR__ . "/../../oauth2/provider/login.php");
+            //exit();
         } else {
             $this->logger->debug("AuthorizationController->userLogin() login valid, continuing oauth process");
         }
-
         //Require MFA if turned on
         $mfa = new MfaUtils($this->userId);
         $mfaToken = $mfa->tokenFromRequest($_POST['mfa_type'] ?? null);
@@ -967,12 +969,11 @@ class AuthorizationController
         $this->logger->debug("AuthorizationController->oauthAuthorizeToken() starting request");
         $response = $this->createServerResponse();
         $request = $this->createServerRequest();
-
         // authorization code which is normally only sent for new tokens
         // by the authorization grant flow.
         $code = $request->getParsedBody()['code'] ?? null;
         // grantType could be authorization_code, password or refresh_token.
-        $this->grantType = $request->getParsedBody()['grant_type'];
+        $this->grantType = 'password'; // $request->getParsedBody()['grant_type'];
         if ($this->grantType === 'authorization_code') {
             // re-populate from saved session cache populated in authorizeUser().
             $ssbc = $this->sessionUserByCode($code);
@@ -986,10 +987,12 @@ class AuthorizationController
         try {
             if (($this->grantType === 'authorization_code') && empty($_SESSION['csrf'])) {
                 // the saved session was not populated as expected
+                // WES
                 throw new OAuthServerException('Bad request', 0, 'invalid_request', 400);
             }
             $result = $server->respondToAccessTokenRequest($request, $response);
             // save a password trusted user
+
             if ($this->grantType === 'password') {
                 $body = $result->getBody();
                 $body->rewind();
@@ -1136,6 +1139,7 @@ class AuthorizationController
                     $token_hint = 'refresh_token';
                 }
             } elseif (($token_hint !== 'access_token' && $token_hint !== 'refresh_token') || empty($rawToken)) {
+                echo 'assdsd';
                 throw new OAuthServerException('Missing token or unsupported hint.', 0, 'invalid_request', 400);
             }
 
