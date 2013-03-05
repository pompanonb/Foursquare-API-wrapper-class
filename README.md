#Foursquare API wrapper class
##About
This Foursquare class enables you to connect to the Foursquare API through PHP. At this point the class only supports the methods that use cURL GET.
The class is well documented inline. By using a decent IDE you can make use of the PHPDOC where everything is written down properly.

This class is released under the MIT license.
If you encounter any bugs. Feel free to fix or report them.

##A quick how-to
* Create an app at Foursquare.
* Include this class in PHP.
    
    <?php include 'foursquare.php'; ?>
    
* Use your app's credentials to initiate the class.
    
    <?php $foursquare = new foursquare(clientID, clientSecret, callbackURL); ?>
    
* Authenticate
    
    <?php $accessToken = $foursquare->authenticate(); ?>
    
* Store you access token
    
    <?php $foursquare->setAccessToken($accessToken); ?>
    
* Use your favourite method
    
    <?php $mayorships = $foursquare->getMayorshipsFromUser(); ?>
    