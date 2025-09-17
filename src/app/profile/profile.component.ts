import { Component, OnInit } from '@angular/core';
import { ApiService } from '../services/api.service';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-profile',
  standalone: true ,
  imports: [CommonModule, FormsModule],
  templateUrl: './profile.component.html'
})
export class ProfileComponent implements OnInit {
  user: any = null;
  transactions: any[] = [];

  constructor(private api: ApiService) {}

  ngOnInit(): void {
    this.api.getCurrentUser().subscribe(res => this.user = res);
    this.api.getMyTransactions().subscribe(res => this.transactions = res);
  }
}

