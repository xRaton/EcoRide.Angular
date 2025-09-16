import { Routes } from '@angular/router';
import { AppComponent } from './app.component';
import { InscriptionComponent } from './inscription/inscription.component';
import { ConnexionComponent } from './connexion/connexion.component';
import { AccueilComponent } from './accueil/accueil.component';


export const routes: Routes = [
    {path:'',component:AccueilComponent},
    {path:'inscription',component:InscriptionComponent},
    {path:'connexion',component:ConnexionComponent},
    {path:'**',redirectTo:''},
];
