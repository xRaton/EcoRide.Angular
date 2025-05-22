import { Routes } from '@angular/router';
import { AppComponent } from './app.component';
import { InscriptionComponent } from './inscription/inscription.component';
import { ConnexionComponent } from './connexion/connexion.component';


export const routes: Routes = [
    // {path:'',component:AppComponent},
    {path:'inscription',component:InscriptionComponent},
    {path:'connexion',component:ConnexionComponent},
    // {path:'**',redirectTo:''}
];
