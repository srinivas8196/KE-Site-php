# MongoDB Bridge System for KE-Site

This guide explains how to use the MongoDB Bridge system we've implemented, which allows you to easily switch between MySQL and MongoDB databases. This setup allows multiple team members to work with the same database remotely.

## How the Bridge Works

We've created a database bridge system that allows your application to work with either MySQL or MongoDB without changing your code. Here's how it works:

1. The `db_bridge.php` file replaces direct database connections
2. You can toggle between MySQL and MongoDB by changing a setting in your `.env` file
3. All database operations work with either database system
4. Your existing PHP files continue to work without modification

## Step 1: Sign Up for MongoDB Atlas

1. Go to [MongoDB Atlas](https://www.mongodb.com/cloud/atlas) and sign up for a free account
2. After signing up, you'll be prompted to create your first cluster
3. Choose the "FREE" tier option
4. Select a cloud provider (AWS, Google Cloud, or Azure) and region closest to your team
5. Click "Create Cluster" (this may take a few minutes to provision)

## Step 2: Set Up Database Access

1. In the left sidebar, click on "Database Access" under the Security section
2. Click the "Add New Database User" button
3. Create a username and password (store these securely as you'll need them for the connection string)
4. For user privileges, select "Atlas admin" for simplicity
5. Click "Add User"

## Step 3: Configure Network Access

1. In the left sidebar, click on "Network Access" under the Security section
2. Click the "Add IP Address" button
3. For development purposes, you can click "Allow Access From Anywhere" (not recommended for production)
4. Click "Confirm"

## Step 4: Get Your Connection String

1. Go back to the Clusters view by clicking "Database" in the left sidebar
2. Click the "Connect" button on your cluster
3. Select "Connect your application"
4. Select "PHP" as your driver and copy the connection string
5. Replace `<password>` in the connection string with the password you created earlier

## Step 5: Update Your .env File

1. Open the .env file in your project
2. Update the MongoDB configuration section with your connection string:
```
MONGODB_URI=mongodb+srv://yourusername:yourpassword@yourcluster.mongodb.net/
MONGODB_DB=karmasite
```

## Step 6: Migrate Data from MySQL to MongoDB (Optional)

If you already have data in your MySQL database, you might want to migrate it to MongoDB. A simple approach is to:

1. Use PHP to export data from MySQL tables
2. Format the data for MongoDB
3. Insert the data into MongoDB collections

## Step 7: Run the Database Migration Script

After setting up MongoDB Atlas, follow these steps to activate the bridge system:

1. Run the migration script to update all your PHP files:
   ```
   php migrate_to_bridge.php
   ```
   This script will:
   - Scan all PHP files in your project
   - Update database include statements to use the bridge
   - Create a backup of your original db.php file
   - Show you which files were updated

2. Open your `.env` file and set `USE_MONGODB=false` to continue using MySQL or `USE_MONGODB=true` to switch to MongoDB

## Step 8: Test the MongoDB Connection

1. Set `USE_MONGODB=true` in your `.env` file
2. Access your application as normal (all pages should work with MongoDB)
3. Try adding a new resort or updating an existing one
4. Verify that the data is saved to MongoDB by checking the MongoDB Atlas dashboard

## How to Switch Between Databases

You can easily switch between MySQL and MongoDB:

- To use MySQL: Set `USE_MONGODB=false` in your `.env` file
- To use MongoDB: Set `USE_MONGODB=true` in your `.env` file

This allows you to:
1. Start with MySQL while setting up MongoDB
2. Test your application with MongoDB
3. Switch between databases as needed
4. Have both team members use the same remote MongoDB database

## Troubleshooting

### MongoDB Connection Issues

If you encounter MongoDB connection issues:

1. Make sure your IP is whitelisted in the Network Access settings of MongoDB Atlas
2. Verify that your username and password are correct in the `.env` file
3. Ensure that the connection string format is correct
4. Check that you've set `USE_MONGODB=true` in your `.env` file

### MySQL Connection Issues

If you encounter MySQL connection issues:

1. Verify your MySQL credentials in the `.env` file
2. Make sure your MySQL server is running
3. Check that you've set `USE_MONGODB=false` in your `.env` file

### Bridge System Issues

If you encounter issues with the bridge system:

1. Make sure you've run the migration script: `php migrate_to_bridge.php`
2. Check that the `db_bridge.php` file exists in your project root
3. Verify that `db_mongo.php` is properly set up

### Data Synchronization

When switching between databases:

1. Your data won't automatically sync between MySQL and MongoDB
2. You may need to export data from one system and import it to the other
3. Consider writing a synchronization script if you need to maintain both databases

## Benefits of Using the Bridge System

1. **Seamless Integration**: Continue using your existing code without major modifications
2. **Flexibility**: Switch between MySQL and MongoDB with a single setting change
3. **Collaboration**: Multiple team members can work with a single remote database
4. **Gradual Migration**: Test MongoDB while keeping MySQL as a fallback
5. **No PHP Extensions Required**: Our file-based solution works without the MongoDB PHP extension