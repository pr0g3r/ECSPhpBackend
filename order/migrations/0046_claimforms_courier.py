# Generated by Django 3.2.7 on 2022-03-30 12:34

from django.db import migrations, models
import django.db.models.deletion


class Migration(migrations.Migration):

    dependencies = [
        ('courier', '0002_courier_claimable'),
        ('order', '0045_alter_order_name'),
    ]

    operations = [
        migrations.AddField(
            model_name='claimforms',
            name='courier',
            field=models.ForeignKey(null=True, on_delete=django.db.models.deletion.SET_NULL, to='courier.courier'),
        ),
    ]
