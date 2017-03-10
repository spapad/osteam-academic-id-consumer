# Web Service Client σε PHP

> OBSOLETE

Υλοποιήθηκε ένας απλός web service client και σε PHP για να πραγματοποιεί τις κλήσεις στο επίσημο web service μέσω php curl.
Οι λειτουργίες του είναι:

* queryID, που επιστρέφει την απάντηση του επίσημου web service, με παραμέτρους:
    * username προεραιτικό, το username για το web service
    * password προεραιτικό, το password για το web service
    * identity υποχρεωτικό, το academic id για έλεγχο
* queryIDis που επιστρέφει κείμενο isStudent:[true,false] όπως προκύπτει από το επίσημο web service, με παραμέτρους:
    * username προεραιτικό, το username για το web service
    * password προεραιτικό, το password για το web service
    * identity υποχρεωτικό, το academic id για έλεγχο
* echo, που επιστρέφει το query_string, με οποιαδήποτε παράμετρο

Οι λειτουργίες δίνονται ως παράμετρος κατά την κλήση. Για παράδειγμα:

* http://local.dev/academic-id/wrapper.php?identity=123456789012 (default is queryID) 
* http://local.dev/academic-id/wrapper.php?username=spapad&identity=123456789012&operation=queryIDis
* http://local.dev/academic-id/wrapper.php?operation=echo&identity=123456789012

Έγιναν αλλαγές στον κώδικα PHP για να είναι 1-1 αντίστοιχες οι λειτουργίες με την υλοποίηση σε java (http://ostmgmt.minedu.gov.gr/projects/wos2_esb/wiki/Pilot_service_Academic_ID)

Οι νέες λειτουργίες είναι: 

* testServiceStatus
* queryIDnoCD
* queryID

Επίσης παράχθηκε [htaccess](.htaccess) αρχείο για να δίνονται ακριβώς ίδια endpoints.
